<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\EmailLog;
class HomeApiController extends Controller
{
    /**
     * Get all box styles (product names) for dropdowns
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBoxStyles()
    {
        try {
            $styles = DB::table('products')
                ->select('id', 'prod_name')
                ->orderBy('prod_name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $styles
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch box styles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get popular custom boxes for mobile app
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPopularBoxes()
    {
        try {
            $popularBoxes = DB::table('products')
                ->where('best_seller', 1)
                ->orderBy('id', 'desc')
                ->limit(12)
                ->get()
                ->map(function ($product) {
                    $prodGallery = is_array($product->prod_gallery)
                        ? $product->prod_gallery
                        : json_decode($product->prod_gallery, true);

                    return [
                        'id' => $product->id,
                        'name' => $product->prod_name,
                        'url' => url(str_replace(' ', '-', strtolower($product->prod_url))),
                        'image' => url('images/' . $product->prod_image),
                        'gallery' => $prodGallery ? array_map(function ($img) {
                            return url('images/' . $img);
                        }, $prodGallery) : [],
                        'meta_title' => $product->meta_title ?? '',
                        'meta_description' => $product->meta_description ?? '',
                    ];
                });

            return response()->json([
                'success' => true,
                'title' => 'Popular Custom Boxes',
                'subtitle' => 'Find The Best Custom Packaging For Every Products!',
                'data' => $popularBoxes,
                'total' => $popularBoxes->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch popular boxes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories for Jan Box Industry
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJanBoxCategories()
    {
        try {
            $categories = DB::table('categories')
                ->where('status', 0)
                ->where('parent_cate', 207)
                ->get();

            $data = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->cate_name ?? '',
                    'img' => $category->cate_image ? url('images/' . $category->cate_image) : '',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by category for Jan Box
     * 
     * @param int $id Category ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJanBoxProductsByCategory($id)
    {
        try {
            $products = DB::table('products')
                ->where('prod_category', $id)
                ->orderBy('id', 'desc')
                ->get();

            $data = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->prod_name ?? '',
                    'img' => $product->prod_image ? url('images/' . $product->prod_image) : '',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => count($data)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**

     * Get style boxes for Jan Box
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJanBoxStyleBoxes()
    {
        try {
            $categories = DB::table('categories')
                ->where('status', 0)
                ->where('parent_cate', 219)
                ->get();

            $data = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->cate_name ?? '',
                    'img' => $category->cate_image ? url('images/' . $category->cate_image) : '',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => count($data)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch style boxes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get printing products for Jan Box
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJanBoxPrintingProducts()
    {
        try {
            $products = DB::table('otherproducts')
                ->orderBy('id', 'desc')
                ->get();

            $data = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->prod_name ?? '',
                    'img' => $product->prod_image ? url('images/' . $product->prod_image) : '',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => count($data)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch printing products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product detail for Jan Box
     * Returns: name, images (gallery), short description
     * 
     * @param int $id Product ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJanBoxProductDetail($id)
    {
        try {
            $product = DB::table('products')->where('id', $id)->first();

            if (!$product) {
                $product = DB::table('otherproducts')->where('id', $id)->first();
            }

            if (!$product) {
                $product = DB::table('cardboardboxes')->where('id', $id)->first();
            }

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $galleryUrls = [];
            if (!empty($product->prod_gallery)) {
                $gallery = json_decode($product->prod_gallery, true);
                if (is_array($gallery)) {
                    foreach ($gallery as $img) {
                        if (!empty($img)) {
                            $galleryUrls[] = url('images/' . $img);
                        }
                    }
                }
            }

            $data = [
                'id' => $product->id,
                'name' => $product->prod_name ?? '',
                'url' => $product->prod_url ? url(str_replace(' ', '-', strtolower($product->prod_url))) : '',
                'img' => $product->prod_image ? url('images/' . $product->prod_image) : '',
                'images' => $galleryUrls,
                'short_description' => $product->prod_short_desc ?? '',
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit a custom quote
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postCustomQuote(Request $request)
    {
        try {
            $product_name = $request->input('prodname', '');
            $name = $request->input('name', $request->input('client_name', ''));
            $email = $request->input('email', '');
            $phone = $request->input('phone', '');
            $length = $request->input('length', '');
            $width = $request->input('width', '');
            $height = $request->input('height', '');
            $unit = $request->input('unit', '');
            $stock = $request->input('stock', '');
            $printing = $request->input('color', '');
            $coating = $request->input('coating', '');
            $cad_sample = $request->input('cad_sample', '');
            $qty = $request->input('qty', '');
            $message = $request->input('message', '');

            $filename = "";
            $fileKey = $request->hasFile('file') ? 'file' : ($request->hasFile('image') ? 'image' : null);

            if ($fileKey) {
                $file = $request->file($fileKey);
                $extension = $file->getClientOriginalName();
                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9.\-]/', '_', $extension);
                $file->move(public_path('images/blog'), $filename);
            }

            $to = "quotes@myboxprinting.com";
            $subject = "Product Request a Quote - App";
            $fileUrl = $filename ? url('images/blog/' . $filename) : 'No file uploaded';

            $htmlContent = ' 
            <html>
                <head>
                </head>
                <body> 
                    <table cellspacing="0" style="width: 600px; border-collapse: collapse;"> 
                      <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Product Name:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $product_name . '</td> 
                        </tr>
                        <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Client Name:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $name . '</td> 
                        </tr> 
                        <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Client Email:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $email . '</td> 
                        </tr> 
                        <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Client Phone:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $phone . '</td> 
                        </tr> 
                         <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Length:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $length . '</td> 
                        </tr> 
                         <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Width:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $width . '</td> 
                        </tr> 
                          <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Height:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $height . '</td> 
                        </tr>
                         <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Unit:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $unit . '</td> 
                        </tr>
                         <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Stock:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $stock . '</td> 
                        </tr>
                         <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Color:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $printing . '</td> 
                        </tr>
                         <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Coating:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $coating . '</td> 
                        </tr>
                        <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">CAD Sample:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $cad_sample . '</td> 
                        </tr>
                        <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Qty:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $qty . '</td> 
                        </tr>
                          <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">File:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $fileUrl . '</td> 
                        </tr>
                          <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Message:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $message . '</td> 
                        </tr>
                    </table> 
                </body> 
            </html>';

            // CRM Logging
            if (class_exists(\App\Helpers\SpamDetector::class) && method_exists(\App\Helpers\SpamDetector::class, 'logInquiry')) {
                \App\Helpers\SpamDetector::logInquiry([
                    'client_name' => $name,
                    'client_email' => $email,
                    'client_phone' => $phone,
                    'product_name' => $product_name ?? 'Custom Quote',
                    'length' => $length,
                    'width' => $width,
                    'height' => $height,
                    'unit' => $unit,
                    'stock' => $stock,
                    'color' => $printing,
                    'coating' => $coating,
                    'quantity' => $qty,
                    'message' => $message,
                    'subject' => $subject,
                    'ip_address' => $request->ip(),
                    'file_url' => $filename ? url('images/blog/' . $filename) : null,
                ]);
            }

            $this->sendEmail($to, $subject, $htmlContent, $email, 'api_custom_quote');

            return response()->json([
                'success' => true,
                'message' => 'Thank you for the inquiry, our sales representative will contact soon!'
            ], 200);

        } catch (\Exception $e) {
            Log::error('ApiProductQuote Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit quote request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit feedback via API
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postFeedback(Request $request)
    {
        try {
            $name = $request->input('name', '');
            $email = $request->input('email', '');
            $subject = $request->input('subject', 'My Box Printing App Feedback');
            $message = $request->input('message', '');

            if (empty($message)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feedback message is required'
                ], 400);
            }

            $to = "support@myboxprinting.com";

            $htmlContent = ' 
            <html>
                <head>
                </head>
                <body> 
                    <table cellspacing="0" style="width: 600px; border-collapse: collapse;"> 
                       
                        <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Subject:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $subject . '</td> 
                        </tr> 
                        <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Message:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $message . '</td> 
                        </tr>
                    </table> 
                </body> 
            </html>';

            $this->sendEmail($to, $subject, $htmlContent, $email, 'api_feedback');

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback!'
            ], 200);

        } catch (\Exception $e) {
            Log::error('ApiFeedback Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit Beat My Price request via API
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postBeatMyPrice(Request $request)
    {
        try {
            $product_name = $request->input('prodname', '');
            $name = $request->input('name', '');
            $email = $request->input('email', '');
            $phone = $request->input('phone', '');
            $length = $request->input('length', '');
            $width = $request->input('width', '');
            $height = $request->input('height', '');
            $unit = $request->input('unit', '');
            $stock = $request->input('stock', '');
            $printing = $request->input('color', '');
            $coating = $request->input('coating', '');
            $cad_sample = $request->input('cad_sample', '');
            $qty = $request->input('qty', '');
            $message = $request->input('message', '');

            $filename = "";
            $fileKey = $request->hasFile('file') ? 'file' : ($request->hasFile('image') ? 'image' : null);

            if ($fileKey) {
                $file = $request->file($fileKey);
                $extension = $file->getClientOriginalName();
                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9.\-]/', '_', $extension);
                $file->move(public_path('images/blog'), $filename);
            }

            $to = "quotes@myboxprinting.com";
            $subject = "Beat My Price - App";
            $fileUrl = $filename ? url('images/blog/' . $filename) : 'No file uploaded';

            $htmlContent = ' 
            <html>
                <head>
                </head>
                <body> 
                    <table cellspacing="0" style="width: 600px; border-collapse: collapse;"> 
                      <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Product Name:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $product_name . '</td> 
                        </tr>
                        <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Client Name:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $name . '</td> 
                        </tr> 
                        <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Client Email:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $email . '</td> 
                        </tr> 
                        <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Client Phone:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $phone . '</td> 
                        </tr> 
                         <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Length:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $length . '</td> 
                        </tr> 
                         <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Width:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $width . '</td> 
                        </tr> 
                          <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Height:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $height . '</td> 
                        </tr>
                         <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Unit:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $unit . '</td> 
                        </tr>
                         <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Stock:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $stock . '</td> 
                        </tr>
                         <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Color:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $printing . '</td> 
                        </tr>
                         <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Coating:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $coating . '</td> 
                        </tr>
                        <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">CAD Sample:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $cad_sample . '</td> 
                        </tr>
                        <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Qty:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $qty . '</td> 
                        </tr>
                          <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">File:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $fileUrl . '</td> 
                        </tr>
                          <tr> 
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;background: #3498db;  color: white;  font-weight: bold;">Message:</th>
                            <td style="padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 18px;">' . $message . '</td> 
                        </tr>
                    </table> 
                </body> 
            </html>';

            // CRM Logging
            if (class_exists(\App\Helpers\SpamDetector::class) && method_exists(\App\Helpers\SpamDetector::class, 'logInquiry')) {
                \App\Helpers\SpamDetector::logInquiry([
                    'client_name' => $name,
                    'client_email' => $email,
                    'client_phone' => $phone,
                    'product_name' => $product_name ?? 'Beat My Price',
                    'length' => $length,
                    'width' => $width,
                    'height' => $height,
                    'unit' => $unit,
                    'stock' => $stock,
                    'color' => $printing,
                    'coating' => $coating,
                    'quantity' => $qty,
                    'message' => $message,
                    'subject' => $subject,
                    'ip_address' => $request->ip(),
                    'file_url' => $filename ? url('images/blog/' . $filename) : null,
                ]);
            }

            $this->sendEmail($to, $subject, $htmlContent, $email, 'api_beat_my_price');

            return response()->json([
                'success' => true,
                'message' => 'Thank you for the inquiry, our sales representative will contact soon!'
            ], 200);

        } catch (\Exception $e) {
            Log::error('ApiBeatMyPrice Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit beat my price request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to send email using Laravel Mail and log to database
     */
    private function sendEmail($to, $subject, $htmlContent, $replyTo = null, $formType = null)
    {
        $fromEmail = config('mail.from.address') ?: 'quotes@myboxprinting.com';
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();

        $emailLog = null;
        if (class_exists(EmailLog::class)) {
            // Create email log entry
            $emailLog = EmailLog::create([
                'to_email' => $to,
                'from_email' => $fromEmail,
                'subject' => $subject,
                'body' => $htmlContent,
                'reply_to' => $replyTo,
                'status' => 'pending',
                'form_type' => $formType,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        }

        try {
            Mail::send([], [], function ($message) use ($to, $subject, $htmlContent, $replyTo, $fromEmail) {
                $message->to($to)
                    ->subject($subject)
                    ->from($fromEmail, config('mail.from.name') ?: 'My Box Printing')
                    ->setBody($htmlContent, 'text/html');

                if ($replyTo) {
                    $message->replyTo($replyTo);
                }
            });

            if ($emailLog) {
                // Update status to sent
                $emailLog->update([
                    'status' => 'sent'
                ]);
            }

            return true;
        } catch (\Exception $e) {
            if ($emailLog) {
                // Update status to failed with error message
                $emailLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            Log::error('Email send error: ' . $e->getMessage());
            return false;
        }
    }
}


