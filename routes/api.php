<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/crm/leads', 'Api\CrmLeadController@store')
    ->middleware(['crm.lead.api', 'throttle:30,1']);

// Test route
Route::get('/test', function () {
    return response()->json(['status' => 'API is working']);
});

// Test product route
Route::get('/test-product', function () {
    return response()->json(['message' => 'Product API test working']);
});

// Dropdown Data APIs
Route::get('/box-styles', [\App\Http\Controllers\Api\HomeApiController::class, 'getBoxStyles']);

// Home Page APIs
Route::get('/popular-boxes', function () {
    try {
        $products = DB::table('products')
            ->where('best_seller', 1)
            ->orderBy('id', 'desc')
            ->limit(12)
            ->get();

        $popularBoxes = [];

        foreach ($products as $product) {
            $prodGallery = null;
            if (!empty($product->prod_gallery)) {
                $prodGallery = json_decode($product->prod_gallery, true);
            }

            $galleryUrls = [];
            if (is_array($prodGallery)) {
                foreach ($prodGallery as $img) {
                    if (!empty($img)) {
                        $galleryUrls[] = url('images/' . $img);
                    }
                }
            }

            $popularBoxes[] = [
                'id' => $product->id,
                'name' => $product->prod_name ?? '',
                'url' => url(str_replace(' ', '-', strtolower($product->prod_url ?? ''))),
                'image' => url('images/' . ($product->prod_image ?? '')),
                'gallery' => $galleryUrls,
                'meta_title' => $product->meta_title ?? '',
                'meta_description' => $product->meta_description ?? '',
            ];
        }

        return response()->json([
            'success' => true,
            'title' => 'Popular Custom Boxes',
            'subtitle' => 'Find The Best Custom Packaging For Every Products!',
            'data' => $popularBoxes,
            'total' => count($popularBoxes)
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch popular boxes',
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
});

// Boxes By Industry API - Fixed version
Route::get('/boxes-by-industry', function () {
    try {
        // Get all active categories first
        $categories = DB::table('categories')
            ->where('status', 0)
            ->get();

        $industryBoxes = [];

        foreach ($categories as $category) {
            // Skip generic parent categories and empty names
            if (
                empty($category->cate_name) ||
                in_array(strtolower($category->cate_name), [
                    'boxes by industry',
                    'boxes by style',
                    'by industry',
                    'by style'
                ])
            ) {
                continue;
            }

            $industryBoxes[] = [
                'id' => $category->id,
                'name' => $category->cate_name ?? '',
                'url' => url(str_replace(' ', '-', strtolower($category->cate_url ?? ''))),
                'image' => url('images/' . ($category->cate_image ?? '')),
                'overlay_image' => url('images/' . ($category->cate_overlay_image ?? '')),
                'banner' => url('images/' . ($category->cate_banner ?? '')),
                'description' => strip_tags($category->cate_long_desc ?? ''),
                'meta_title' => $category->meta_title ?? '',
                'meta_description' => $category->meta_description ?? '',
                'show_on_home' => $category->show_on_home ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'title' => 'Boxes By Industry',
            'subtitle' => 'Custom Packaging Solutions for Every Industry',
            'data' => $industryBoxes,
            'total' => count($industryBoxes)
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch industry boxes',
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
});

// Boxes By Style API
Route::get('/boxes-by-style', function () {
    try {
        $styleBoxes = DB::table('cardboardboxes')
            ->orderBy('id', 'desc')
            ->get();

        $boxesByStyle = [];

        foreach ($styleBoxes as $box) {
            $prodGallery = null;
            if (!empty($box->prod_gallery)) {
                $prodGallery = json_decode($box->prod_gallery, true);
            }

            $galleryUrls = [];
            if (is_array($prodGallery)) {
                foreach ($prodGallery as $img) {
                    if (!empty($img)) {
                        $galleryUrls[] = url('images/' . $img);
                    }
                }
            }

            $relatedProducts = null;
            if (!empty($box->related_prod)) {
                $relatedProducts = json_decode($box->related_prod, true);
            }

            $boxesByStyle[] = [
                'id' => $box->id,
                'name' => $box->prod_name ?? '',
                'url' => url(str_replace(' ', '-', strtolower($box->prod_url ?? ''))),
                'image' => url('images/' . ($box->prod_image ?? '')),
                'gallery' => $galleryUrls,
                'short_description' => $box->prod_short_desc ?? '',
                'long_description' => $box->prod_long_desc ?? '',
                'alt_name' => $box->prod_altname ?? '',
                'meta_title' => $box->meta_title ?? '',
                'meta_description' => $box->meta_description ?? '',
                'related_products' => $relatedProducts,
            ];
        }

        return response()->json([
            'success' => true,
            'title' => 'Boxes By Style',
            'subtitle' => 'Explore Different Box Styles and Designs',
            'data' => $boxesByStyle,
            'total' => count($boxesByStyle)
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch style boxes',
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
});

// Printing Products API
Route::get('/printing-products', function () {
    try {
        $printingProducts = DB::table('otherproducts')
            ->orderBy('id', 'desc')
            ->get();

        $products = [];

        foreach ($printingProducts as $product) {
            $prodGallery = null;
            if (!empty($product->prod_gallery)) {
                $prodGallery = json_decode($product->prod_gallery, true);
            }

            $galleryUrls = [];
            if (is_array($prodGallery)) {
                foreach ($prodGallery as $img) {
                    if (!empty($img)) {
                        $galleryUrls[] = url('images/' . $img);
                    }
                }
            }

            $relatedProducts = null;
            if (!empty($product->related_prod)) {
                $relatedProducts = json_decode($product->related_prod, true);
            }

            $products[] = [
                'id' => $product->id,
                'name' => $product->prod_name ?? '',
                'url' => url(str_replace(' ', '-', strtolower($product->prod_url ?? ''))),
                'image' => url('images/' . ($product->prod_image ?? '')),
                'gallery' => $galleryUrls,
                'short_description' => $product->prod_short_desc ?? '',
                'long_description' => $product->prod_long_desc ?? '',
                'alt_name' => $product->prod_altname ?? '',
                'meta_title' => $product->meta_title ?? '',
                'meta_description' => $product->meta_description ?? '',
                'related_products' => $relatedProducts,
            ];
        }

        return response()->json([
            'success' => true,
            'title' => 'Printing Products',
            'subtitle' => 'Professional Digital Printing Services',
            'data' => $products,
            'total' => count($products)
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch printing products',
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
});

// Navigation Menu API - All sections combined
Route::get('/navigation-menu', function () {
    try {
        // Get popular boxes (limited to 6 for menu)
        $popularBoxes = DB::table('products')
            ->where('best_seller', 1)
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get(['id', 'prod_name', 'prod_url', 'prod_image']);

        // Get industry categories (limited to 8 for menu)
        $industries = DB::table('categories')
            ->where('status', 0)
            ->where('parent_cate', 0)
            ->orderBy('id', 'asc')
            ->limit(8)
            ->get(['id', 'cate_name', 'cate_url', 'cate_image']);

        // Get style boxes (limited to 6 for menu)
        $styleBoxes = DB::table('cardboardboxes')
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get(['id', 'prod_name', 'prod_url', 'prod_image']);

        // Get printing products (limited to 6 for menu)
        $printingProducts = DB::table('otherproducts')
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get(['id', 'prod_name', 'prod_url', 'prod_image']);

        $menuData = [
            'popular_boxes' => $popularBoxes->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->prod_name ?? '',
                    'url' => url(str_replace(' ', '-', strtolower($item->prod_url ?? ''))),
                    'image' => url('images/' . ($item->prod_image ?? ''))
                ];
            }),
            'boxes_by_industry' => $industries->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->cate_name ?? '',
                    'url' => url(str_replace(' ', '-', strtolower($item->cate_url ?? ''))),
                    'image' => url('images/' . ($item->cate_image ?? ''))
                ];
            }),
            'boxes_by_style' => $styleBoxes->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->prod_name ?? '',
                    'url' => url(str_replace(' ', '-', strtolower($item->prod_url ?? ''))),
                    'image' => url('images/' . ($item->prod_image ?? ''))
                ];
            }),
            'printing_products' => $printingProducts->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->prod_name ?? '',
                    'url' => url(str_replace(' ', '-', strtolower($item->prod_url ?? ''))),
                    'image' => url('images/' . ($item->prod_image ?? ''))
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'title' => 'Navigation Menu',
            'subtitle' => 'Complete menu structure for mobile app',
            'data' => $menuData
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch navigation menu',
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
});

// Individual Product Detail API
Route::get('/product/{id}', function ($id) {
    try {
        $product = DB::table('products')
            ->where('id', $id)
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $prodGallery = null;
        if (!empty($product->prod_gallery)) {
            $prodGallery = json_decode($product->prod_gallery, true);
        }

        $galleryUrls = [];
        if (is_array($prodGallery)) {
            foreach ($prodGallery as $img) {
                if (!empty($img)) {
                    $galleryUrls[] = url('images/' . $img);
                }
            }
        }

        $relatedProducts = null;
        if (!empty($product->related_prod)) {
            $relatedProducts = json_decode($product->related_prod, true);
        }

        $productDetail = [
            'id' => $product->id,
            'name' => $product->prod_name ?? '',
            'url' => url(str_replace(' ', '-', strtolower($product->prod_url ?? ''))),
            'image' => url('images/' . ($product->prod_image ?? '')),
            'gallery' => $galleryUrls,
            'short_description' => $product->prod_short_desc ?? '',
            'long_description' => strip_tags($product->prod_long_desc ?? ''),
            'alt_name' => $product->prod_altname ?? '',
            'meta_title' => $product->meta_title ?? '',
            'meta_description' => $product->meta_description ?? '',
            'meta_tags' => $product->meta_tags ?? '',
            'category_id' => $product->prod_category ?? 0,
            'best_seller' => $product->best_seller ?? 0,
            'new_arrival' => $product->new_arrival ?? 0,
            'feature_prod' => $product->feature_prod ?? 0,
            'stock_status' => $product->stock_status ?? 1,
            'related_products' => $relatedProducts,
        ];

        return response()->json([
            'success' => true,
            'data' => $productDetail
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch product details',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Category Products API
Route::get('/category/{id}/products', function ($id) {
    try {
        $category = DB::table('categories')
            ->where('id', $id)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $products = DB::table('products')
            ->where('prod_category', $id)
            ->orderBy('id', 'desc')
            ->get();

        $categoryProducts = [];

        foreach ($products as $product) {
            $prodGallery = null;
            if (!empty($product->prod_gallery)) {
                $prodGallery = json_decode($product->prod_gallery, true);
            }

            $galleryUrls = [];
            if (is_array($prodGallery)) {
                foreach ($prodGallery as $img) {
                    if (!empty($img)) {
                        $galleryUrls[] = url('images/' . $img);
                    }
                }
            }

            $categoryProducts[] = [
                'id' => $product->id,
                'name' => $product->prod_name ?? '',
                'url' => url(str_replace(' ', '-', strtolower($product->prod_url ?? ''))),
                'image' => url('images/' . ($product->prod_image ?? '')),
                'gallery' => $galleryUrls,
                'short_description' => $product->prod_short_desc ?? '',
                'meta_title' => $product->meta_title ?? '',
                'meta_description' => $product->meta_description ?? '',
                'best_seller' => $product->best_seller ?? 0,
                'new_arrival' => $product->new_arrival ?? 0,
                'feature_prod' => $product->feature_prod ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $category->id,
                'name' => $category->cate_name ?? '',
                'description' => strip_tags($category->cate_long_desc ?? ''),
                'image' => url('images/' . ($category->cate_image ?? '')),
                'banner' => url('images/' . ($category->cate_banner ?? '')),
            ],
            'products' => $categoryProducts,
            'total' => count($categoryProducts)
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch category products',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Search Products API
Route::get('/search', function (Request $request) {
    try {
        $query = $request->get('q', '');

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required'
            ], 400);
        }

        $products = DB::table('products')
            ->where('prod_name', 'LIKE', '%' . $query . '%')
            ->orderBy('best_seller', 'desc')
            ->limit(20)
            ->get();

        $searchResults = [];

        foreach ($products as $product) {
            $searchResults[] = [
                'id' => $product->id,
                'name' => $product->prod_name ?? '',
                'url' => url(str_replace(' ', '-', strtolower($product->prod_url ?? ''))),
                'image' => url('images/' . ($product->prod_image ?? '')),
                'short_description' => $product->prod_short_desc ?? '',
                'meta_title' => $product->meta_title ?? '',
                'best_seller' => $product->best_seller ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'query' => $query,
            'results' => $searchResults,
            'total' => count($searchResults)
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Search failed',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Latest Blogs API - Final version
Route::get('/latest-blogs', function () {
    try {
        $blogs = DB::table('blogs')
            ->orderBy('time', 'DESC')
            ->orderBy('t_id', 'DESC')

            ->get();

        $latestBlogs = [];

        foreach ($blogs as $blog) {
            $latestBlogs[] = [
                'id' => $blog->t_id ?? 0,
                'name' => $blog->t_title ?? 'Blog Post',
                'image_url' => !empty($blog->t_featured_image) ? url('images/blog/' . $blog->t_featured_image) : url('images/blog/default-blog.jpg'),
                'blog_url' => !empty($blog->t_slug) ? url('blog/' . $blog->t_slug) : url('blog/' . $blog->t_id),
                'date' => $blog->time ?? date('Y-m-d'),
            ];
        }

        return response()->json([
            'success' => true,
            'title' => 'Latest Blogs',
            'subtitle' => 'Stay Updated with Our Latest Articles',
            'data' => $latestBlogs,
            'total' => count($latestBlogs)
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch blogs',
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
});

// Individual Blog Detail API - Fixed version
Route::get('/blog-detail/{id}', function ($id) {
    try {
        $blog = DB::table('blogs')
            ->where('t_id', $id)
            ->first();

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog not found'
            ], 404);
        }

        $blogDetail = [
            'id' => $blog->t_id ?? 0,
            'name' => $blog->t_title ?? 'Blog Post',
            'image_url' => !empty($blog->t_featured_image) ? url('images/blog/' . $blog->t_featured_image) : url('images/blog/default-blog.jpg'),
            'blog_url' => !empty($blog->t_slug) ? url('blog/' . $blog->t_slug) : url('blog/' . $blog->t_id),
            'date' => $blog->time ?? date('Y-m-d'),
            'meta_description' => $blog->metadesc ?? '',
            'keywords' => $blog->keywords ?? '',
            'author' => $blog->t_author ?? 'My Box Printing',
            'tags' => $blog->tags_clouds ?? '',
        ];

        return response()->json([
            'success' => true,
            'data' => $blogDetail
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch blog details',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Jan Box Industry APIs
Route::get('/jan-box-industry', [\App\Http\Controllers\Api\HomeApiController::class, 'getJanBoxCategories']);
Route::get('/jan-box-category-products/{id}', [\App\Http\Controllers\Api\HomeApiController::class, 'getJanBoxProductsByCategory']);
Route::get('/jan-box-style-boxes', [\App\Http\Controllers\Api\HomeApiController::class, 'getJanBoxStyleBoxes']);
Route::get('/jan-box-printing-products', [\App\Http\Controllers\Api\HomeApiController::class, 'getJanBoxPrintingProducts']);
Route::get('/jan-box-product/{id}', [\App\Http\Controllers\Api\HomeApiController::class, 'getJanBoxProductDetail']);

// Custom Quote API
Route::post('/custom-quote', [\App\Http\Controllers\Api\HomeApiController::class, 'postCustomQuote']);

// Feedback API
Route::post('/feedback/', [\App\Http\Controllers\Api\HomeApiController::class, 'postFeedback']);

// Firebase Auth Sync
Route::post('/sync-firebase-user', [\App\Http\Controllers\Api\AuthController::class, 'syncFirebaseUser']);
Route::post('/delete-firebase-user', [\App\Http\Controllers\Api\AuthController::class, 'deleteFirebaseUser']);

// Beat My Price API
Route::post('/beat-my-price', [\App\Http\Controllers\Api\HomeApiController::class, 'postBeatMyPrice']);

// Store Custom Project API
Route::post('/store-custom-project', [\App\Http\Controllers\Api\ProjectApiController::class, 'store']);

// Projects & Orders
Route::get('/custom-projects', [\App\Http\Controllers\Api\ProjectApiController::class, 'index']);
Route::post('/custom-projects', [\App\Http\Controllers\Api\ProjectApiController::class, 'store']);
Route::post('/projects/{id}/place-order', [\App\Http\Controllers\Api\ProjectApiController::class, 'placeOrder']);

// Production Order Tracking
Route::get('/projects/orders', [\App\Http\Controllers\Api\ProjectApiController::class, 'indexOrders']);
Route::get('/projects/orders/{id}', [\App\Http\Controllers\Api\ProjectApiController::class, 'showOrder']);
Route::post('/projects/orders/{id}/cancel/', [\App\Http\Controllers\Api\ProjectApiController::class, 'cancelOrder']);

// Dieline APIs
Route::prefix('projects/{projectId}/dielines')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\DielineApiController::class, 'index']);
});

Route::prefix('dielines')->group(function () {
    Route::post('/upload/', [\App\Http\Controllers\Api\DielineApiController::class, 'store']);
    Route::post('/{id}/status/', [\App\Http\Controllers\Api\DielineApiController::class, 'updateStatus']);
    Route::post('/{id}/rename/', [\App\Http\Controllers\Api\DielineApiController::class, 'rename']);
    Route::delete('/{id}/', [\App\Http\Controllers\Api\DielineApiController::class, 'destroy']);
});

Route::prefix('mockups')->group(function () {
    Route::post('/{id}/status/', [\App\Http\Controllers\Api\DielineApiController::class, 'updateMockupStatus']);
});

// User Mockup Upload from App
Route::post('/dielines/{dielineId}/upload-mockup', [\App\Http\Controllers\Api\DielineApiController::class, 'uploadMockupFromApp']);

// User Request Company Mockup Design
Route::post('/dielines/{dielineId}/request-mockup', [\App\Http\Controllers\Api\DielineApiController::class, 'requestCompanyMockup']);

// Sample Order APIs
Route::prefix('samples')->group(function () {
    Route::post('/request/', [\App\Http\Controllers\Api\SampleOrderController::class, 'store']);
    Route::get('/', [\App\Http\Controllers\Api\SampleOrderController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Api\SampleOrderController::class, 'show']);
    Route::post('/{id}/cancel/', [\App\Http\Controllers\Api\SampleOrderController::class, 'cancelSample']);
});

// Dieline Credit & Webhook routes
Route::get('/credits', [\App\Http\Controllers\Api\CreditsController::class, 'credits']);
Route::post('/credits/verify-purchase', [\App\Http\Controllers\Api\CreditsController::class, 'verifyPurchase']);
Route::post('/dielines/generate-proxy', [\App\Http\Controllers\Api\CreditsController::class, 'generateProxy']);
Route::post('/webhooks/apple', [\App\Http\Controllers\Api\CreditsController::class, 'webhookApple']);
Route::post('/webhooks/google', [\App\Http\Controllers\Api\CreditsController::class, 'webhookGoogle']);
