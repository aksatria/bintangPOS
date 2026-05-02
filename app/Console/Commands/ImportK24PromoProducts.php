<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;

class ImportK24PromoProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import-k24-promo
        {--mode=replace : Import mode: replace or sync}
        {--max-pages=0 : Maximum pages to crawl, 0 means crawl until the source has no next page}
        {--source=https://www.k24klik.com/cariObat/promo : K24 promo source URL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import K24 promo products and images into the POS catalog';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $mode = (string) $this->option('mode');

        if (! in_array($mode, ['replace', 'sync'], true)) {
            $this->error('Mode harus replace atau sync.');

            return self::FAILURE;
        }

        $source = (string) $this->option('source');
        $maxPages = max((int) $this->option('max-pages'), 0);
        $products = $this->crawlProducts($source, $maxPages);

        if ($products === []) {
            $this->error('Tidak ada produk yang berhasil dibaca dari sumber K24.');

            return self::FAILURE;
        }

        $category = Category::query()->firstOrCreate(
            ['name' => 'K24 Promo'],
            ['description' => 'Produk promo impor dari K24Klik.', 'is_active' => true],
        );

        DB::transaction(function () use ($category, $mode, $products): void {
            if ($mode === 'replace') {
                Product::query()
                    ->where('category_id', $category->id)
                    ->delete();

                Product::query()
                    ->where('category_id', '!=', $category->id)
                    ->update(['is_active' => false]);
            }

            foreach ($products as $product) {
                Product::query()->updateOrCreate(
                    ['sku' => $product['sku']],
                    [
                        'category_id' => $category->id,
                        'name' => $product['name'],
                        'barcode' => null,
                        'purchase_price' => round($product['selling_price'] * 0.8),
                        'selling_price' => $product['selling_price'],
                        'stock' => $product['stock'],
                        'low_stock_threshold' => 10,
                        'unit' => $product['unit'],
                        'image' => $product['image'],
                        'is_active' => true,
                    ],
                );
            }
        });

        $this->info("Selesai import {$products->count()} produk K24 promo.");

        return self::SUCCESS;
    }

    private function crawlProducts(string $source, int $maxPages): \Illuminate\Support\Collection
    {
        $url = $source;
        $page = 1;
        $products = collect();
        $seenSkus = [];

        while ($url !== '') {
            $this->line("Mengambil halaman {$page}: {$url}");

            $response = Http::timeout(30)
                ->retry(2, 500)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 POS Importer',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);

            if (! $response->successful()) {
                $this->warn("Halaman gagal diambil: {$url}");
                break;
            }

            [$pageProducts, $nextUrl] = $this->parseProducts($response->body(), $url);

            foreach ($pageProducts as $product) {
                if (isset($seenSkus[$product['sku']])) {
                    continue;
                }

                $product['image'] = $this->downloadImage($product['image_url'], $product['sku']);
                unset($product['image_url']);

                $seenSkus[$product['sku']] = true;
                $products->push($product);
            }

            if ($nextUrl === null || ($maxPages > 0 && $page >= $maxPages)) {
                break;
            }

            $url = $nextUrl;
            $page++;
        }

        return $products;
    }

    private function parseProducts(string $html, string $currentUrl): array
    {
        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $anchors = $xpath->query('//a[contains(@onclick, "showProductDetail")][.//span[@itemprop="name"]]');
        $products = [];

        foreach ($anchors as $anchor) {
            $name = $this->normalize($xpath->evaluate('string(.//span[@itemprop="name"])', $anchor));
            $imageUrl = $xpath->evaluate('string(.//img[contains(@alt, "Apotek Online")]/@src)', $anchor);
            $text = $this->normalize($anchor->textContent ?? '');
            $price = $this->extractPrice($text);

            if ($name === '' || $imageUrl === '' || $price === null) {
                continue;
            }

            $productId = $this->extractProductId((string) $anchor->getAttribute('onclick'), $name);
            $products[] = [
                'name' => $name,
                'sku' => 'K24-'.$productId,
                'selling_price' => $price['amount'],
                'unit' => $price['unit'] ?: 'pcs',
                'stock' => 100,
                'image_url' => $this->absoluteUrl($imageUrl, $currentUrl),
            ];
        }

        $next = $xpath->evaluate('string(//div[contains(@class, "infinite_navigation")]//a[contains(., "next")]/@href)');

        return [$products, $next !== '' ? $this->absoluteUrl($next, $currentUrl) : null];
    }

    private function extractPrice(string $text): ?array
    {
        preg_match_all('/Rp\s*([\d.]+)(?:\s*\/\s*([A-Za-z]+))?/i', $text, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            return null;
        }

        $match = end($matches);

        return [
            'amount' => (int) str_replace('.', '', $match[1]),
            'unit' => isset($match[2]) ? Str::lower($match[2]) : null,
        ];
    }

    private function extractProductId(string $onclick, string $name): string
    {
        if (preg_match('/-([0-9]+)(?:[\'"])?\s*\)?\s*;?$/', $onclick, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\/p\/[^\'"]+-([0-9]+)/', $onclick, $matches)) {
            return $matches[1];
        }

        return substr(sha1($name), 0, 10);
    }

    private function downloadImage(string $url, string $sku): ?string
    {
        try {
            $response = Http::timeout(30)
                ->retry(2, 500)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 POS Importer'])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg';
            $extension = Str::lower(preg_replace('/[^a-z0-9]/i', '', $extension) ?: 'jpg');
            $path = 'products/k24/'.Str::slug($sku).'.'.$extension;

            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }

    private function absoluteUrl(string $url, string $currentUrl): string
    {
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        $base = parse_url($currentUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? 'www.k24klik.com';

        if (Str::startsWith($url, '/')) {
            return "{$scheme}://{$host}{$url}";
        }

        return "{$scheme}://{$host}/{$url}";
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }
}
