@include('frontend/header')


<style>

.accordion-button:not(.collapsed) {
    color: #ffffff !important;
    background-color: #86c542 !important;
    box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.125);
}



    .cp-banner-height {
    min-height:auto !important;

        
        
    }
    
 .cp-testimonial2-icon2 {
    left: 8px;
       top: 80px !important;

    -webkit-transform: rotate(180deg);
    -moz-transform: rotate(180deg);
    -ms-transform: rotate(180deg);
    transform: rotate(180deg);
}
.cp-testimonial2-icon1 {
    right: 15px !important;
    bottom: 23px !important;
}

    .cp-testimonial2-text p {
   
    line-height: 30px !important;
}


 .cp-testimonial2-text p{
     font-size:20px !important;
 }
</style>
 <style>
     .nice-select{
         display: none !important;
     }
     
     .product-single .product-thumb{
         background: none !important;
     }
 
        .select-items div, .select-selected{
            background-color: white !important;color: black;
        }
  
      .cp-quote-title span{
          background: none !important;
      }
  </style>

<style>
                              .custom-center-section {
  text-align: center;
  margin-top: 200px;
}

@media (max-width: 768px) {
  .custom-center-section {
    margin-top: 100px; /* smaller on tablets/mobile */
  }
}

@media (max-width: 480px) {
  .custom-center-section {
    margin-top: 60px; /* even smaller on small phones */
  }
}

/* Default for desktop */
.cp-banner-area1 {
    width: 100%;
    height: 450px;
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
}


/* Media Query for mobile (max-width: 767px) */
@media (max-width: 767px) {
    .cp-banner-area1 {
        height: 250px; /* or use 40vh if you want it dynamic */
    }
}
@media (min-width: 1024px) {
    .bg_slider_desktop {
        display: none !important;
    }
}
@media only screen and (min-width: 576px) and (max-width: 767px), (max-width: 575px) {
    .page-title-area {
        height: 120px !important;
    }
}

/* Hero Section Responsive Styles */
.hero-section {
    display: block;
}

.hero-section-mobile {
    display: none;
}

@media (max-width: 991px) {
    .hero-section {
        display: none !important;
    }
    
    .hero-section-mobile {
        display: block !important;
    }
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 36px !important;
    }
    
    .hero-subtitle {
        font-size: 18px !important;
    }
}

@media (max-width: 576px) {
    .hero-title {
        font-size: 28px !important;
    }
    
    .hero-subtitle {
        font-size: 16px !important;
    }
    
    .trust-badges img {
        height: 30px !important;
    }
}

/* Hover effect for CTA button */
.hero-btn:hover {
    background-color: #75b032 !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(134, 195, 66, 0.3);
}


                          </style>
   <!-- Add your site or application content here -->
   <main>
          <section class="page-title-area breadcrumb-spacing cp-bg-14">
       
         
        </section>
        
        <!-- Hero Section: Half Text, Half Image -->
        <section class="hero-section" style="background: linear-gradient(135deg, #c6d8b7ff 0%, #e8e8e8 100%); padding: 0; min-height: 500px; overflow: hidden;">
            <div class="row align-items-center" style="margin: 0;">
                <!-- Left Side: Text Content -->
                <div class="col-lg-6 col-md-12" style="padding: 20px 20px;">
                    <div class="container">
                        <div class="hero-text-content">
                            <h1 class="hero-title" style="font-size: 32px; font-weight: 700; color: #2c2c2c; line-height: 1.3; margin-bottom: 15px;">
                                {{$our_home_slider[0]->mini_title}}
                            </h1>
                            <p class="hero-subtitle" style="font-size: 18px; color: #4d4b4bff; margin-bottom: 25px; line-height: 1.6;">
                                {{$our_home_slider[0]->slider_description}}
                            </p>
                            
                            <!-- CTA Button -->
                            <div class="hero-cta" style="margin-bottom: 20px;">
                                <a href="{{url('get-quote')}}" class="hero-btn" style="display: inline-block; padding: 12px 30px; background-color:#86C342; color:white !important; font-size: 16px; font-weight: 600; border-radius: 5px; text-decoration: none; transition: all 0.3s ease; border: none;">
                                    Request Quote
                                </a>
                            </div>
                            
                            <!-- Trust Badges -->
                            <div class="logos-wrapper" style="display: flex; gap: 20px; align-items: center; flex-wrap: nowrap;">
                                <!-- Google Reviews Badge -->
                                <div class="review-badge google-badge" style="display: inline-flex; background: transparent; border: none; padding: 0; align-items: center; gap: 10px;">
                                  <a href="https://g.page/r/CXoS0VYj1SlmEBI/review" target="_blank" rel="noopener noreferrer" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;">
                                    <svg class="google-g-icon" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px;">
                                      <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                      <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                      <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                      <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                                    </svg>
                                    <span class="google-text" style="font-size: 13px; color: #5f6368; font-weight: 400;">Review</span>
                                    <span class="rating-number" style="font-size: 24px; font-weight: 700; color: #202124; line-height: 1;">5.0</span>
                                    <div class="stars" style="display: flex; gap: 2px;">
                                      <span class="star" style="color: #FBBC04; font-size: 16px;">★</span>
                                      <span class="star" style="color: #FBBC04; font-size: 16px;">★</span>
                                      <span class="star" style="color: #FBBC04; font-size: 16px;">★</span>
                                      <span class="star" style="color: #FBBC04; font-size: 16px;">★</span>
                                      <span class="star" style="color: #FBBC04; font-size: 16px;">★</span>
                                    </div>
                                  </a>
                                </div>
                                
                                <!-- TrustPilot Badge -->
                                <div class="review-badge trustpilot-badge" style="display: inline-flex; background: transparent; border: none; padding: 0; align-items: center; gap: 10px;">
                                  <a href="https://www.trustpilot.com/review/myboxprinting.com" target="_blank" rel="noopener noreferrer" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;">
                                    <svg class="tp-star-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px;">
                                      <path fill="#00B67A" d="M12 0L15.09 7.26L23 8.27L17.5 13.14L18.82 21.02L12 17.27L5.18 21.02L6.5 13.14L1 8.27L8.91 7.26L12 0Z"/>
                                    </svg>
                                    <span class="tp-text-logo" style="font-size: 16px; font-weight: 700; color: #191919;">Trustpilot</span>
                                    <div class="tp-stars-row" style="display: flex; gap: 2px;">
                                      <span class="tp-star-box" style="background: #00B67A; color: white; font-size: 12px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">★</span>
                                      <span class="tp-star-box" style="background: #00B67A; color: white; font-size: 12px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">★</span>
                                      <span class="tp-star-box" style="background: #00B67A; color: white; font-size: 12px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">★</span>
                                      <span class="tp-star-box" style="background: #00B67A; color: white; font-size: 12px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">★</span>
                                      <span class="tp-star-box half" style="background: linear-gradient(90deg, #00B67A 50%, #dcdce6 50%); color: white; font-size: 12px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">★</span>
                                    </div>
                                    <div class="tp-score-section" style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
                                      <span class="tp-excellent" style="font-size: 12px; font-weight: 600; color: #191919;">Excellent</span>
                                      <span class="tp-score" style="font-size: 11px; font-weight: 400; color: #5f6368;">4.9/5</span>
                                    </div>
                                  </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side: Product Images - Full Width -->
                <div class="col-lg-6 col-md-12" style="padding: 0;">
                    <div class="hero-image-content" style="position: relative; width: 100%; min-height: 500px; display: flex; align-items: center; justify-content: center;">
                        <img src="{{ url('images') . '/' . $our_home_slider[0]->slider_banner }}" 
                             alt="{{$our_home_slider[0]->mini_title}}" 
                             class="img-fluid" 
                             width="800"
                             height="500"
                             fetchpriority="high"
                             loading="eager"
                             decoding="async"
                             style="width: 100%; height: auto; object-fit: contain; max-height: 500px;">
                    </div>
                </div>
            </div>
        </section>
        
        <section class="hero-section-mobile d-lg-none" style="background: linear-gradient(135deg, #c6d8b7ff 0%, #e8e8e8 100%); padding: 40px 0;">
            <div class="container-fluid" style="padding: 0 10px;">
                <div class="text-center mb-4">
                    <img src="{{ url('images') . '/' . $our_home_slider[0]->slider_banner }}" 
                         alt="{{$our_home_slider[0]->mini_title}}" 
                         class="img-fluid" 
                         width="450"
                         height="300"
                         fetchpriority="high"
                         loading="eager"
                         decoding="async"
                         style="width: 100%; max-width: 450px; transform: scale(1.1); margin-bottom: 25px;">
                </div>

                <div class="text-center mb-4">
                    <h1 style="font-size: 28px; font-weight: 700; color: #2c2c2c; margin-bottom: 15px; line-height: 1.2;">
                        {{$our_home_slider[0]->mini_title}}
                    </h1>
                    <p style="font-size: 17px; color: #4d4b4bff; margin-bottom: 25px; line-height: 1.5; padding: 0 10px;">
                        {{$our_home_slider[0]->slider_description}}
                    </p>
                    
                    <div class="text-center" style="margin-top: 30px; margin-bottom: 30px;">
                        <a href="{{url('get-quote')}}" class="hero-btn" style="display: inline-block; padding: 14px 35px; background-color:#86C342; color:white !important; font-size: 17px; font-weight: 600; border-radius: 5px; text-decoration: none; box-shadow: 0 4px 15px rgba(134, 195, 66, 0.3);">
                            Request Quote
                        </a>
                    </div>

                    <!-- Trust Badges for Mobile -->
                    <div class="logos-wrapper mt-40" style="display: flex; flex-direction: column; gap: 25px; align-items: center; justify-content: center; margin: 0 10px;">
                        <!-- Google Reviews Badge -->
                        <div class="review-badge google-badge" style="display: inline-flex; background: transparent; border: none; padding: 0; align-items: center; gap: 10px;">
                          <a href="https://g.page/r/CXoS0VYj1SlmEBI/review" target="_blank" rel="noopener noreferrer" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;">
                            <svg class="google-g-icon" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px;">
                              <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                              <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                              <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                              <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            </svg>
                            <span class="google-text" style="font-size: 13px; color: #5f6368; font-weight: 400;">Review</span>
                            <span class="rating-number" style="font-size: 24px; font-weight: 700; color: #202124; line-height: 1;">5.0</span>
                            <div class="stars" style="display: flex; gap: 2px;">
                              <span class="star" style="color: #FBBC04; font-size: 16px;">★</span>
                              <span class="star" style="color: #FBBC04; font-size: 16px;">★</span>
                              <span class="star" style="color: #FBBC04; font-size: 16px;">★</span>
                              <span class="star" style="color: #FBBC04; font-size: 16px;">★</span>
                              <span class="star" style="color: #FBBC04; font-size: 16px;">★</span>
                            </div>
                          </a>
                        </div>
                        
                        <!-- TrustPilot Badge -->
                        <div class="review-badge trustpilot-badge" style="display: inline-flex; background: transparent; border: none; padding: 0; align-items: center; gap: 10px;">
                          <a href="https://www.trustpilot.com/review/myboxprinting.com" target="_blank" rel="noopener noreferrer" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;">
                            <svg class="tp-star-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px;">
                              <path fill="#00B67A" d="M12 0L15.09 7.26L23 8.27L17.5 13.14L18.82 21.02L12 17.27L5.18 21.02L6.5 13.14L1 8.27L8.91 7.26L12 0Z"/>
                            </svg>
                            <span class="tp-text-logo" style="font-size: 16px; font-weight: 700; color: #191919;">Trustpilot</span>
                            <div class="tp-stars-row" style="display: flex; gap: 2px;">
                              <span class="tp-star-box" style="background: #00B67A; color: white; font-size: 12px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">★</span>
                              <span class="tp-star-box" style="background: #00B67A; color: white; font-size: 12px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">★</span>
                              <span class="tp-star-box" style="background: #00B67A; color: white; font-size: 12px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">★</span>
                              <span class="tp-star-box" style="background: #00B67A; color: white; font-size: 12px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">★</span>
                              <span class="tp-star-box half" style="background: linear-gradient(90deg, #00B67A 50%, #dcdce6 50%); color: white; font-size: 12px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">★</span>
                            </div>
                            <div class="tp-score-section" style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
                              <span class="tp-excellent" style="font-size: 12px; font-weight: 600; color: #191919;">Excellent</span>
                              <span class="tp-score" style="font-size: 11px; font-weight: 400; color: #5f6368;">4.9/5</span>
                            </div>
                          </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
  
      <section class="cp-services-area pb-20 p-relative z-index-1 mt--20 pt-40 " >
         

         <div class="container">
            <div class="row align-items-end">
                
                  
<div class="cp-cta2-title mb-20 hide-on-mobile" >
    <p style="font-size: 30px; color: rgb(33 37 41 / var(--tw-text-opacity,1)); margin-top: -10px;    font-weight: 600;">
        Select Your Packaging Style  
        <span class="separator-text" style="font-weight: 100;">No Matter The Product, We Have The Perfect Packaging Styles!</span>
          <span class="" style="  font-size:17px;  font-weight: 600;color:#86C342;"><a href="{{url('by-industry/').'/'}}">View All ></a> </span>
    </p>
</div>
 <?php $products = DB::table('categories')->where('parent_cate','207')->limit(6)->get(); ?>

@foreach($products as $index => $pp)
<div class="col-xl-2 col-lg-4 col-md-6 col-6">
    <a href="{{ url(str_replace(' ', '-', strtolower($pp->cate_url))) . '/' }}">
        <div class="cp-services-item t-center mb-30" style="border-radius: 0px !important;">
            
            <div class="cp-services-img w-img" style="border-radius: 100%; overflow: hidden; width: 150px; height: 150px; margin: 0 auto;">
                <img 
                    src="{{ url('images/' . $pp->cate_image) }}" 
                    alt="{{ $pp->cate_name }}" 
                    width="150"
                    height="150"
                    loading="lazy"
                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; padding: 5px;"
                >
            </div>

            <h3 class="for-mobile mt-20" style="font-size:16px !important;">{{ $pp->cate_name }}</h3>
        </div>
    </a>
</div>
@endforeach

 
            </div>
         </div>
      </section>
     
      
      <section class="cp-cta2-area pt-10 pb-10 cp-video-overlay bg-fixed bg-css" style="height: 70px;">
         <div class="container">
            <div class="cp-cta2-wrap scene d-flex align-items-center justify-content-between flex-wrap flex-xl-nowrap ow fadeInUp animated" data-wow-duration="2.3s" data-wow-delay="0.3s">
               <div class=" cp-cta2-title mb-20">
                  <p style="font-size:20px;color:white;    margin-top: 0px;
" > Order Now & <span style="font-size: larger;color: #85c83f;"> Save 20% </span> on Your First Purchase!</p>
               </div>
               <div class="cp-cta2-btn mb-20" style="margin-top: -5px;">
                  <div class="cp-header-btn d-none d-md-block">
                           <a class="cp-btn" href="{{url('get-quote').'/'}}" style="background:#86C342;">
                         Order Now
                              
                           </a>
                        </div>
                
                  </a>
               </div>
            </div>
         </div>
      </section>
      
            
            <section class="cp-services-area pb-20 p-relative z-index-1 mt--20 pt-20" >
            
            <div class="container  hide-on-mobile" >
            <div class="row align-items-end">
            
            
            <div class="cp-cta2-title mb-0 pt-20" >
            <p style="font-size: 30px; color: rgb(33 37 41 / var(--tw-text-opacity,1));    font-weight: 600;"> Popular Custom Boxes <span class="separator-text" style="font-weight: 100;">Find The Best Custom Packaging For Every Products!</span>
            <span class="" style="  font-size:17px;  font-weight: 600;color:#86C342;">
            <a href="{{url('retail-boxes/').'/'}}">View All ></a>
            </span>
            </p>
            </div>
            
            </div></div>
            
            </section> 


    <div class="product-area" style="">
        
         <div class="container">
 


<div class="cp-product-wrap"  style="visibility: visible; animation-duration: 1.5s; animation-delay: 0.5s; animation-name: fadeInUp;">
    <div class="row grid">
  
      @foreach ($feature_prod_home11 as $promItem1)
    <div class="col-xl-2 col-lg-4 col-md-6 col-6 grid-item">
        <div class="">
            <div class="" style="background:none !important;">
                <a href="{{ url(str_replace(' ', '-', strtolower($promItem1->prod_url))) . '/' }}" class="">
                    @php
                        $imgName = $promItem1->prod_image;
                        $imgNameReadable = preg_replace('/\d+/', '', str_replace(['.webp', '.png', '.jpg', '-', '.'], [' ', '', '', ' ', ''], $imgName));
                        $prodGallery = is_array($promItem1->prod_gallery) ? $promItem1->prod_gallery : json_decode($promItem1->prod_gallery, true);
                        $firstGalleryImage = !empty($prodGallery) ? $prodGallery[1] : null;
                    @endphp
                    <div class="cp-services-item t-center">
                        <div class="cp-services-img w-img">
                            
                    <img 
                        style="border-radius:20px; padding: 5px; width:100%; height: auto;" 
                        src="{{ url('images/' . $promItem1->prod_image) }}" 
                        alt="{{ trim($imgNameReadable) }}"
                        width="200"
                        height="200"
                        loading="lazy"
                    >
                    
                        </div>
                    </div>
                </a>

                <ul class="product-links">
                    <li>
                        <a href="{{ url(str_replace(' ', '-', strtolower($promItem1->prod_url))) . '/' }}"></a>
                    </li>
                </ul>
            </div>

            <div class="product-description" style="background-color:white !important;">
                <div style="height: 45px; overflow: hidden; text-align:center;">
                    <h3 class="for-mobilexx" style="font-size:16px !important; line-height:1.3em;">
                       <a href="{{ url(str_replace(' ', '-', strtolower($promItem1->prod_url))) . '/' }}">  {{ \Illuminate\Support\Str::limit($promItem1->prod_name, 55) }}</a>
                    </h3>
                </div>
            </div>
        </div>
    </div>
@endforeach

        
    </div>
</div>

 

            
         </div>
      </div>
      
    
         
      <section class="cp-cta2-area pt-10 pb-10 cp-video-overlay bg-fixed bg-css" style="height: 70px;">
         <div class="container">
            <div class="cp-cta2-wrap scene d-flex align-items-center justify-content-between flex-wrap flex-xl-nowrap ow fadeInUp animated" data-wow-duration="2.3s" data-wow-delay="0.3s">
               <div class=" cp-cta2-title mb-20">
                  <p style="font-size:20px;color:white;    margin-top: 0px;
" > Order Now & <span style="font-size: larger;color: #85c83f;"> Save 20% </span> on Your First Purchase!</p>
               </div>
               <div class="cp-cta2-btn mb-20" style="margin-top: -5px;">
                  <div class="cp-header-btn d-none d-md-block">
                           <a class="cp-btn" href="{{url('get-quote').'/'}}" style="background:#86C342;">
                         Order Now
                              
                           </a>
                        </div>
            
                  </a>
               </div>
            </div>
         </div>
      </section>
      
             <section class="cp-services-area pb-20 p-relative z-index-1 mt--20 pt-20 hide-on-mobile" >
            
            <div class="container">
            <div class="row align-items-end">
            
            
            <div class="cp-cta2-title mb-0 pt-20" >
            <p style="font-size: 30px; color: rgb(33 37 41 / var(--tw-text-opacity,1));    font-weight: 600;">   Elevate Your Brand - Add A Touch of Luxury <span class="separator-text" style="font-weight: 100;">Find The Best Custom Packaging For Every Products!</span>
            <span class="" style="  font-size:17px;  font-weight: 600;color:#86C342;">
            <a href="{{url('printing-products/').'/'}}">View All ></a>
            </span>
            </p>
            </div>
            
            </div></div>
            
            </section> 


        <section class="cp-services-area pb-20 p-relative z-index-1 mt--20 pt-40 " >
       
         <div class="container">
            <div class="row align-items-end">
                
                  
<!--<div class="cp-cta2-title mb-20" >-->
<!--    <p style="font-size: 30px; color: rgb(33 37 41 / var(--tw-text-opacity,1)); margin-top: -10px;    font-weight: 600; text-align:center;">-->
 
         
<!--    </p>-->
<!--</div>-->
 <?php
$products1 = DB::table('otherproducts')
    ->whereIn('prod_name', ['Decals Printing', 'Business Cards','Tags Printing','Vinyl Banners','Booklets','Brochures'])
    ->limit(6)
    ->get();
?>

@foreach($products1 as $index => $pp)
<div class="col-xl-2 col-lg-4 col-md-6 col-6">
    <a href="{{ url(str_replace(' ', '-', strtolower($pp->prod_url))) . '/' }}">
        <div class="cp-services-item t-center mb-30" style="border-radius: 0px !important;">
            
               
            <div class="cp-services-img w-img" style="border-radius: 100%; overflow: hidden; width: 150px; height: 150px; margin: 0 auto;">
                <img 
                    src="{{ url('images/' . $pp->prod_image) }}" 
                    alt="{{ $pp->prod_name }}" 
                    width="150"
                    height="150"
                    loading="lazy"
                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; padding: 5px;"
                >
            </div>

            <h3 class="for-mobile mt-20" style="font-size:16px !important;">{{ $pp->prod_name }}</h3>
        </div>
    </a>
</div>
@endforeach

 
            </div>
         </div>
      </section>
     
      
     <section class="products bg_pro displaynoned" style="background-color:#E9F6F0;">
            <div class="container py-4">
 
         <span style=" font-weight: 600;font-size: 20px;text-align:center;">ONE PLACE - Where you get all your custom packaging needs</span>
 
               
            </div>
            <div class="container ">
                <div class="row">

                    <div class="col-lg-2 col-md-4 col-sm-4 col-4 col-6">
                        <div class="text-center">
                            <div class="fbox-icon">
                                <i class="i-alt i-light noborder"><img src="{{url('no-die-&-plate-charges.webp')}}"
                                                                       loading="lazy" width="99" height="99" alt="no die & plate charges"></i>
                            </div>
                            <p class="point-name pt-2 text-uppercase">No die & <br>Plate Charges</p>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-sm-4 col-4 col-6">
                        <div class="text-center">
                            <div class="fbox-icon">
                                <i class="i-alt i-light noborder"><img src="{{url('quick-turnaroun-time.png')}}"
                                                                       loading="lazy" width="99" height="99" alt="quick turnaroun time"></i>
                            </div>
                            <p class="point-name pt-2 text-uppercase">Quick <br> Turnaround<br>Time

                            </p>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-sm-4 col-4 col-6">
                        <div class="text-center">
                            <div class="fbox-icon">
                                <i class="i-alt i-light noborder"><img
                                        src="{{url('free-shippings.png')}}" loading="lazy" width="99"
                                        height="99" alt="free shipping "></i>
                            </div>
                            <p class="point-name pt-2 text-uppercase">Free  <br>Shipping</p>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-sm-4 col-4 col-6">
                        <div class="text-center">
                            <div class="fbox-icon">
                                <i class="i-alt i-light noborder"><img src="{{url('Starting-from-50-boxes.png')}}"
                                                                       loading="lazy" width="99" height="99" alt="Starting from 50 boxes"></i>
                            </div>
                            <p class="point-name  pt-2 text-uppercase ">Starting From <br> 50 Boxes</p>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-sm-4 col-4 col-6">
                        <div class="text-center">
                            <div class="fbox-icon">
                                <i class="i-alt i-light noborder"><img src="{{url('Customize-size-&-style.png')}}"
                                                                       loading="lazy" width="99" height="99" alt="Customize size & style"></i>
                            </div>
                            <p class="point-name pt-2 text-uppercase">Customize Size <br> & Style</p>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-sm-4 col-4 col-6">
                        <div class="text-center">
                            <div class="fbox-icon">
                                <i class="i-alt i-light noborder"><img src="{{url('free-graphic-designing.png')}}"
                                                                       loading="lazy" width="99" height="99" alt="free graphic designing"></i>
                            </div>
                            <p class="point-name pt-2 text-uppercase ">Free Graphic <br> designing</p>
                        </div>
                    </div>

                    <div class="clear"></div>

                </div>
            </div>
        </section>
      

             
            <section class="cp-services-area pb-20 p-relative z-index-1 mt--20 pt-20 hide-on-mobile" >
         
         <div class="container">
            <div class="row align-items-end">
                
                  <style>.separator-text::before{margin-left: 10px;}</style>
<div class="cp-cta2-title mb-0 pt-20 mt-20" >
 <p style="font-size: 30px; color: rgb(33 37 41 / var(--tw-text-opacity,1)); margin-top: -20px;    font-weight: 600;">Customer Reviews<span class="separator-text" style="font-weight: 100;">
  See why clients trust us through their honest, real feedback read MyBoxPrinting reviews
 </span>
                <span class="" style="  font-size:17px;  font-weight: 600;color:#86C342;">
              
                </span>
              </p>
</div>

</div>
      <div class="d-none d-sm-block">
         <div class="cp-testimonial2-nav cp-slider-round-button-wrap d-flex justify-content-end cp-test-space zi-5 p-relative">
            <div class="cp-slider-round-button cp-testimonial2-button-prev"><i class="fas fa-chevron-left"></i></div>
            <div class="cp-slider-round-button cp-testimonial2-button-next"><i class="fas fa-chevron-right"></i></div>
         </div>
      </div>
      </div>

 </section> 
 
 
 
 <section class="cp-testimonial2-area pt-20 pb-20 hide-on-mobile">
   <div class="container">
      <div class="row">
         <div class="swiper-container cp-testimonial2-active">
            <div class="swiper-wrapper">
               @foreach ($our_testimonial as $testimonial_slider)
               <div class="swiper-slide col-md-6 col-12" style="    background-color: #F6F9FE;
    border-radius: 20px;">
                  <div class="cp-testimonial2-item-wrap mb-20">
                     <div class="cp-testimonial2-item">
                        <div class="cp-testimonial2-text p-relative">
                   
                         
                           <div class="cp-testimonial2-author" style="margin-inline-start:0px !important;">
                           <div>
                              <img src="{{ url('images').'/'.$testimonial_slider->slider_image }}" alt="{{ $testimonial_slider->main_title }}" style="width:60px;height:60px; border-radius:50px;">
                           </div>
                          
                           <div class="cp-testimonial2-author-text">
                              <h3>{{ $testimonial_slider->main_title }}</h3>
                               <i class="fas fa-star" style="color: #FFD700;"></i>
                           <i class="fas fa-star" style="color: #FFD700;"></i>
                           <i class="fas fa-star" style="color: #FFD700;"></i>
                           <i class="fas fa-star" style="color: #FFD700;"></i>
                           <i class="fas fa-star" style="color: #FFD700;"></i>
                           </div>
                           
                        </div>
                        
                           <p><div class="cp-testimonial2-icon cp-testimonial2-icon2 p-absolute">
                                       <i class="fas fa-quote-right"></i>
                                    </div>{{ $testimonial_slider->slider_description }}
                                    <div class="cp-testimonial2-icon cp-testimonial2-icon1 p-absolute">
                                       <i class="fas fa-quote-right"></i>
                                    </div>
                                    </p>
                        </div>
                        
                     </div>
                  </div>
               </div>
               @endforeach
            </div>
         </div>
      </div>

      <!-- Navigation Buttons -->

   </div>
</section>

      
 
       


      
           <section class="cp-feature-area p-relative cp-bg-2 zi-1 pt-10 pb-10" style="background-color:#D6F3E5 !important;">
         
         <div class="container">
             
              <div class="row justify-content-center">
               <div class="col-xl-12 col-lg-12">
                        <div class="cp-cta2-title mb-0 pt-30" >
            <p style="font-size: 30px; color: rgb(33 37 41 / var(--tw-text-opacity,1)); margin-top: -10px;    font-weight: 600;">Order a Sample Kit
           <span class="hide-on-mobile separator-text" style="font-weight: 100;">Order a MyBoxPrinting Sample Kit to see our premium materials and print quality firsthand.</span>
            
            </p>
            </div>
            </div>
            
        
            <div class="row">
      
               <div class="col-xl-8 col-lg-8 col-sm-6">
                 
                    <div class="cp-quote-wrapperss" style="">
                    
                     <div class="cp-quote-form">
                        <form action="{{url('product_form_submit11').'/'}}" method="post">
                     
                            @csrf
                           <div class="cp-quote-box mb-10"> 
                              <div class="row">
                                 <div class="col-lg-4">
                                    <div class="cp-input-field">
                                   
                                       <input type="text" id="name" name="name" required placeholder="Name *">
                                   
                                    </div>
                                 </div>
                                 <div class="col-lg-4">
                                    <div class="cp-input-field">
                                   
                                       <input type="email" id="email" name="email" required placeholder="E Mail*">
                                      
                                    </div>
                                 </div>
                                 <div class="col-lg-4">
                                    <div class="cp-input-field">
                                     
                                       <input type="text" id="phone" name="phone" required placeholder="Phone Number *">
                             
                                    </div>
                                 </div>
                              
                           
                                 
                                   <div class="col-xl-3 col-lg-6">
                                    <div class="cp-input-field">
                                 
                                       <input type="text" id="width" name="width" required placeholder="Width *">
                                      
                                    </div>
                                 </div>
                                 <div class="col-xl-3 col-lg-6">
                                    <div class="cp-input-field">
                                  
                                       <input type="text" id="height" name="height" required placeholder="Height *">
                                   
                                    </div>
                                 </div>
                                 <div class="col-xl-3 col-lg-6">
                                    <div class="cp-input-field">
                                       
                                       <input type="text" id="length" name="length" required placeholder="Length *">
                                   
                                    </div>
                                 </div>
                                  <div class="col-xl-3 col-lg-6">
                                    <div class="cp-input-field">
                                    
                                       <input type="text" id="length" name="qty" required placeholder="Quantity *">
                               
                                    </div>
                                 </div>
                                 
                
    
                                <?php $pp=DB::table('products')->get();?>
                    
          <div class="col-xl-4 col-lg-4">
              
              
              <div class="custom-select" style="width:100%;">
  <select name="prodname" style=" ">
       <option value="">Select Your Box Style</option>
   @foreach($pp as $p)
            <option value="{{$p->prod_name}}">{{$p->prod_name}}</option>
        @endforeach
  </select>
</div>
 

 
                                       
                                       
                                       
 
    </div>
 


                                 <div class="col-xl-4 col-lg-6 for-the-mobile">
                                    <div class="custom-select" style="width:100%;">
                                     
                                     <select class="form-control" name="stock" data-gtm-form-interact-field-id="3">
                                        <option value="Paper Stock">Select Paper Stock</option> 
  <option value="12pt Cardboard Stock">12pt Cardboard Stock</option>
  <option value="14pt Cardboard Stock">14pt Cardboard Stock</option>
  <option value="16pt Cardboard Stock">16pt Cardboard Stock</option>
  <option value="18pt Cardboard Stock">18pt Cardboard Stock</option>
  <option value="20pt Cardboard Stock">20pt Cardboard Stock</option>
  <option value="22pt Cardboard Stock">22pt Cardboard Stock</option>
  <option value="24pt Cardboard Stock">24pt Cardboard Stock</option>
  <option value="Kraft Stock">Kraft Stock</option>
  <option value="Recycled BuxBoard">Recycled BuxBoard</option>
  <option value="Corrugated Stock">Corrugated Stock</option>
  <option value="No Printing Required">No Printing Required</option>
</select>
 
                                    </div>
                                 </div>
                                 
                                 <div class="col-xl-4 col-lg-6 for-the-mobile">
                                     <div class="custom-select" style="width:100%;">
                                  
                                    <select class="form-control" name="color">
                                        <option>Select Color</option>
  <option value="1 colour">1 Colour</option>
  <option value="2 colour">2 Colour</option>
  <option value="3 colour">3 Colour</option>
  <option value="4 colour">4 Colour</option>
  <option value="4/1 colour">4/1 Colour</option>
  <option value="4/2 colour">4/2 Colour</option>
  <option value="4/3 colour">4/3 Colour</option>
  <option value="4/4 colour">4/4 Colour</option>
</select>
 
                                    </div>
                                 </div>
                                 
                                 
                                 
                                        <div class="col-xl-4 col-lg-6 mt-20" style="">
                                   <div class="custom-select" style="width:100%;">
<select class="form-control" name="coating" data-gtm-form-interact-field-id="0">
    <option value="Paper Coating">Select Paper Coating</option>
  <option value="Aqueous Coating">Aqueous Coating</option>
  <option value=" Semi Gloss"> Semi Gloss</option>
  <option value="Gloss UV">Gloss UV</option>
  <option value="Matte UV">Matte UV</option>
  <option value="Semi Matte">Semi Matte</option>
</select>
 
                                    </div>
                                 </div>
                                 
                                 <div class="col-xl-4 col-lg-6 mt-20">
                             <div class="custom-select" style="width:100%;">
                                    
<select class="form-control" name="cad_sample" data-gtm-form-interact-field-id="2">
    <option value="CAD Sample">Select CAD Sample</option>
  <option value="Yes">Yes</option>
  <option value="No">No</option>
</select>
 
                                    </div>
                                 </div>
                                         
    
                                 <div class="col-xl-4 col-lg-6 mt-20">
                                   <div class="custom-select" style="width:100%;">
                                  
                                       <select name="unit" style="">
                                             <option value="select units">Select Units</option>
                                          <option value="MM">MM</option>
                                          <option value="CM">CM</option>
                                      
                                       </select>
                                    </div>
                                 </div>
                                           
                           
                                    <div class="col-xl-12 mt-20">
                                    <div class="cp-input-field">
                                     
                                       <input type="text" id="phone" name="message" placeholder="Your Message">
                                    
                                    </div>
                                 </div>
                              </div>
                           </div>
                           <script src="https://www.google.com/recaptcha/enterprise.js" async defer></script>
                           <div class="cp-quote-btn mb-10" style="    text-align: center;">
                               <div class="cp-input-field">
                                   <div class="g-recaptcha" id="home-quote-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}" data-callback="onHomeQuoteCaptchaSuccess"></div>
                                   <div id="home-quote-captcha-error" style="color: #fff; font-size: 14px; font-weight: bold; margin-top: 10px; display: none; padding: 12px; background: #dc3545; border-radius: 5px;">
                                       ⚠️ Please complete the reCAPTCHA verification before submitting
                                   </div>
                               </div>
                              <button type="submit" class="cp-border2-btn" style="background-color:#86C342; color:white;">Instant Quote</button>
                           </div>
                        </form>
                        
                        <script>
                        var homeQuoteCaptchaCompleted = false;
                        
                        function onHomeQuoteCaptchaSuccess(token) {
                            homeQuoteCaptchaCompleted = true;
                            var errorDiv = document.getElementById('home-quote-captcha-error');
                            if (errorDiv) {
                                errorDiv.style.display = 'none';
                            }
                        }
                        
                        (function() {
                            function initHomeQuoteFormValidation() {
                                var forms = document.querySelectorAll('form[action*="product_form_submit11"]');
                                var errorDiv = document.getElementById('home-quote-captcha-error');
                                
                                if (forms.length === 0) return;
                                
                                forms[0].onsubmit = function(event) {
                                    if (!homeQuoteCaptchaCompleted) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        event.stopImmediatePropagation();
                                        
                                        if (errorDiv) {
                                            errorDiv.style.display = 'block';
                                            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                        }
                                        
                                        return false;
                                    }
                                    
                                    if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.getResponse === 'function') {
                                        var response = grecaptcha.getResponse();
                                        if (!response || response.length === 0) {
                                            event.preventDefault();
                                            event.stopPropagation();
                                            event.stopImmediatePropagation();
                                            
                                            if (errorDiv) {
                                                errorDiv.style.display = 'block';
                                                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                            }
                                            
                                            homeQuoteCaptchaCompleted = false;
                                            return false;
                                        }
                                    }
                                    
                                    if (errorDiv) {
                                        errorDiv.style.display = 'none';
                                    }
                                    
                                    return true;
                                };
                            }
                            
                            if (document.readyState === 'loading') {
                                document.addEventListener('DOMContentLoaded', initHomeQuoteFormValidation);
                            } else {
                                initHomeQuoteFormValidation();
                            }
                        })();
                        </script>
                     </div>
                  </div>
               </div>
               <div class="col-xl-4 col-lg-4 col-sm-6">
                   <img src="{{url('custom-boxes-sample-kit.webp')}}" style="margin-top: 0px;" alt="box-sample-kit">
               </div>
            </div>
         </div>
      </section>
<style>
    .video-container {
        width: 100%;
        max-width: 1000px;
        margin: 0 auto;
        background-color: #000;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .video-container video {
        width: 100%;
        height: auto;
        display: block;
        border-radius: 8px;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }

    @media (max-width: 768px) {
        body {
            padding: 10px;
        }
    }
</style>

<section class="cp-services-area pb-20 p-relative z-index-1 mt--20 pt-20">
    <div class="container"> 
        <div class="video-container" style="position: relative;">
            <video id="scrollVideo" muted playsinline controls>
                <source src="{{ url('custom-boxes.mp4') }}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    </div>
</section>



    
 <style>
     .SecSec {
    display: none;
}
.showmorecontent .SecSec {
    display: inline;
}
.readLess {
    display: none;
}
.showmorecontent .readMore {
    display: none;
}
.showmorecontent .readLess {
    display: inline;
}

 </style>
<!-- <section class="cp-testimonial2-area pt-10 pb-10" style="width:100%;">-->
     
<!--     <div class="container">-->
<!--          <div class="row justify-content-center">-->
<!--               <div class="col-xl-7 col-lg-12">-->
<!--                  <div class="cp-services2-title-wrap t-center mb-35">-->
<!--                     <div class="cp-section-title">-->
                       
<!--                        <h2 class="cp-title mb-25 " style="visibility: visible; animation-duration: 1.5s; animation-delay: 0.4s; animation-name: fadeInUp;font-size:35px;">-->
<!--How Its -->
<!--  <span> Work </span>  </h2>-->
<!--                             <p style="text-align:justify;">Discover premium and inspiring products designed for quality, style, and innovation, enhancing your everyday life with excellence.</p>-->
<!--                     </div>-->
<!--                  </div>-->
<!--               </div>-->
<!--            </div>-->
<!--     </div>-->
            
            
            
<!--         <div class="container-fluid">-->
<!--               <div class="row justify-content-center">-->
<!--               <div class="col-xl-12 col-lg-12">-->
<!--                   <img src="{{url('how_its_work.png')}}" style="width:100%;">-->
<!--                   </div>-->
<!-- </div>-->
<!-- </div>-->
<!-- </section>-->
 <style>
#section {
  width: 100%;
  
  word-wrap: break-word;
}

.moretext {
  display: none;
}

</style>
<section class="cp-news3-area p-relative pt-10 pb-10 mb-10 fix mt-20 ">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                
                <div class="container">
            
            <div id="section">
  <div class="article">
    <h1 style="font-size:24px;">
     Order Custom Boxes, Custom Packaging & Shipping Boxes At Wholesale Rates

    </h1>
   <p style="text-align:justify;color:black;">
        Do you have a business? Big or small doesn’t matter!

    </p>
     <p style="text-align:justify;color:black;">
        Want to give your customers elegance with great packaging and a secure product within? Look no further!

    </p>
    <p style="text-align:justify;color:black;">
Here we are, My Box Printing offers wholesale, unbeatable rates that are unbelievable for the quality we are providing. We understand our clients and their business needs. Business is like a baby that needs to be taken care of with great products that won't get harmed in any way.

   </p>
   <p style="text-align:justify;color:black;">
    In the same way, your brand needs to deliver with our custom packaging and material so that your customer feels luxurious with the feel of the product packaging and printing.

   </p>
   
   <p style="text-align:justify;color:black;">
We are a company that provides a complete packaging solution at retail and wholesale prices. Let's put our services into major points.

   </p>
 
    
     <div class="moretext">
         
         
         <ul style="margin-left: 40px;">
        <li style="list-style-type: disc !important;color:black;">
                 Custom Boxes
             </li>
       <li style="list-style-type: disc !important;color:black;">
                 Custom Packaging
             </li>
        <li style="list-style-type: disc !important;color:black;">
              Custom Box Designs

             </li>
      <li style="list-style-type: disc !important;color:black;">
                 <a style="color:#86C442;" href="{{url('mailer-boxes').'/'}}">Custom Mailer Boxes</a>

             </li>
          <li style="list-style-type: disc !important;color:black;">
                 Custom Printed Boxes
             </li>
      <li style="list-style-type: disc !important;color:black;">
                 Custom Gift Packaging

             </li>
         </ul>
         <p></p>
      <p style="text-align:justify;color:black;">
     Not only this, we also offer mock-ups and complete satisfaction, in which our team will review your design and make the changes needed. We have a great 3d design studio that gives you a visual representation of what you are going to receive after the order.

    </p>
    
    
       <p style="text-align:justify;color:black;">
      The design team also makes sure to keep every aspect of your brand in mind. After the order is completed, all is packed in corrugated cardboard shipping boxes, which are also sustainable packaging and eco-friendly.


       </p>
    
      <p style="text-align:justify;color:black;">
          
          
          
          
No matter how unique the design you want, we'll give you your expected results. We design and ship within due time.

      </p>
      
      
      
      
      
      
      
      
    <h2 style="font-size:22px;color:black;"> Custom Design </h2>
    
    
       <p style="text-align:justify;color:black;"> Let's talk more about you because it is not us, it is you who is the focus. Do you want a different or rare shape for your product? A unique box design may be corrugated or a subscription box. Not only this, customize your box style with high-quality corrugated cardboard material so that your clients can feel a great unboxing experience, or if you think you can’t find your kind of packaging. We will make this happen for you. Any size or any customization you want can be provided within the time frame.


</p>
 

<p style="color:black;">
Not only this we also ship to our clients before deadlines, which makes us more reliable and more efficient.

</p>
<span style="color:black;">
  <center><b><i>“We are a company of trust that values its customers.”</i></b></center>
</span>


    <p></p>
 <h2 style="font-size:22px;color:black;"> 
     Concept To Creation


    </h2>
     <p style="text-align:justify;color:black;"> 
Any big thing has been a concept first. We work in every aspect of the packaging with an eye for perfection. Let's talk in detail about what My Box Printing is providing you.


     </p>
    
    
    
    
 
    
    
        
     <h3 style="font-size:18px;color:black;"> 
Customization


    </h3>
     <p style="text-align:justify;color:black;"> 
Customization is famous among new entrepreneurs who are into a product and want their customers to feel special without using the product. The customer should have a vibe of a great product via package unboxing.

     </p>
    
    
        <p style="text-align:justify;color:black;">
Custom boxes are the specialty here at My Box Printing. We offer infinite designs to show our customers and have a great team of graphic designers that collectively work on your project to make it look extraordinary.
</p>
      
       
    
          
      
  <h3 style="font-size:18px;">
Premium Quality


    </h3>
    <p style="text-align:justify;color:black;">
The next thing that comes to mind is the quality, what quality you are looking for, and what we are providing. We assure the best quality of custom boxes at your doorstep within a few business days. We are also always aware of the market trends and how to make a product that stands out.



    </p>
    
    
    
          
       <h3 style="font-size:18px;font-weight:600;">Durability With Design

 </h3>
       <p style="text-align:justify;color:black;">
We not only offer the design, but we also offer the quality that speaks for itself. Also, the packaging is durable, so that there will be no harm to your product.  Being a packaging company, we know that the items can be fragile or CBD.

              
       


</p>
 
          <p style="text-align:justify;color:black;">
So, in all cases, we offer custom solutions to many of your problems, including protection and safe delivery.




</p>

       <h3 style="font-size:18px;font-weight:600;">Your Brand Is Special, So We Are
</h3>


    <p style="text-align:justify;color:black;">
We understand that your brand means a lot to you, and for us as well. Customer satisfaction is the most important part. We are here to make your brand stand out in terms of custom packaging and printing. 



</p>
    
        
       <h3 style="font-size:18px;font-weight:600;">Material Quality

</h3>
<p style="text-align:justify;color:black;">
The material we use is of high quality and biodegradable, which makes us a sustainable packaging company. We love how our clients entrust their brand with us. So we ensure that the final product is according to their demands in terms of quality and perfection.




</p>

<p style="text-align:justify;color:black;">
    The material we use are as follows;

</p>

<ul style="margin-left: 40px;">
    <li style="list-style-type: disc !important; color:black;">
        Corrugated cardboard

    </li>
   <li style="list-style-type: disc !important;color:black;">
        Kraft
    </li>
     <li style="list-style-type: disc !important;color:black;">Paperboard</li>
</ul>

<p></p>

       <h2 style="font-size:22px;font-weight:600;color:black;">Most Commonly Used Boxes

</h2>
 

        <p style="text-align:justify;color:black;">
The  <a style="color:#86C442;"  href="{{url('cardboard-boxes').'/'}}">Corrugated Cardboard Boxes  </a> are highly protective and are also lightweight. They are the shield to your order. They can be customized according to the size of your product and are durable for your products.
            </p>



</p>


       <h3 style="font-size:18px;font-weight:600;">Kraft Boxes  

</h3>


  <p style="text-align:justify;color:black;">
The<a style="color:#86C442;"  href="{{url('kraft-boxes').'/'}}"> Kraft boxes</a> are in great demand because they are budget-friendly. They are used in various industries, including food & beverage, retail, and the cosmetics industry. They are eco-friendly and light as a feather.




</p>



       <h3 style="font-size:18px;font-weight:600;color:black;">Shipping Cardboard Boxes
</h3>



  <p style="text-align:justify;color:black;">
<a style="color:#86C442;"  href="{{url('custom-shipping-boxes').'/'}}">Shipping cardboard boxes</a> is used in every industry for shipping purposes. They are sturdy and have preventive qualities. They can be customized in thickness and size as well. At My Box Printing, we also offer printed shipping boxes that enhance your brand's existence.


</p>

</p>


  <h3 style="font-size:18px;color:black;"> Rigid boxes 

 </h3>

 <p style="text-align:justify;color:black;">
On the other hand,<a style="color:#86C442;"  href="{{url('luxury-rigid-boxes').'/'}}"> Rigid boxes</a> are premium, high-end boxes for your luxury products. We offer the best rates and also give you the best results in terms of customization and printing.


</p>

 <p style="text-align:justify;color:black;">
They are mostly ordered by the people who want to make their brand a luxury. For example, the perfume industry, watch industry, and cosmetic industry, etc.
 

</p>

<h2 style="font-size:22px;color:black;">
    Some Important Types of Custom Boxes

</h2>
 <p style="text-align:justify;color:black;">
Some of our customizations and services in boxes are described below.

</p>

  <ul style="display: flex; flex-wrap: wrap; padding: 0; margin: 0;">
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">Custom Boxes</li>
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">Retail Boxes</li>
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">
   Rigid Boxes
  </li>
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;"> <a style="color:#86C442;" href="{{url('gift-boxes').'/'}}">Gift Boxes</a></li>
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">
    <a style="color:#86C442;" href="{{url('cosmetic-boxes').'/'}}">Cosmetic Boxes</a>
  </li>
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">
    <a style="color:#86C442;" href="{{url('cbd-packaging').'/'}}">CBD Packaging</a>
  </li>
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">
    <a style="color:#86C442;" href="{{url('mylar-bags').'/'}}">Mylar Bags</a>
  </li>
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">
    <a style="color:#86C442;" href="{{url('food-and-beverage').'/'}}">Food and Beverages Boxes</a>
  </li>
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">
    <a style="color:#86C442;" href="{{url('bakery-boxes').'/'}}">Bakery Boxes</a>
  </li>
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">Apparel Gift Boxes</li>
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">Premium Box Packaging</li>
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">Custom Corrugated Boxes</li>
    <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">Custom Shipping Boxes</li>
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">Pop-Up Lid Boxes</li>
  <li style="flex: 0 0 25%; list-style-type: disc !important; margin-left: 20px;">Bottom Closure</li>
  
</ul>

<p></p>
<p style="text-align:justify; color:black;">
  <center><b><i>“And much more. You just name it, we have it.”</i></b></center>
</p>

<h2 style="font-size:22px;color:black;">
    My Box Printing: ONE SPOT For Custom Packaging And Custom Printing

</h2>
 <p style="text-align:justify;color:black;">
    Let me tell you why you need to pick us as your partner to work on your brand.

</p>

<h3 style="font-size:18px;color:black;">
Custom Boxes & Custom Printing
</h3>
 <p style="text-align:justify;color:black;">
    We not only offer custom boxes but also offer custom printing services at the same time, which saves a lot of time and money as well.

</p>
 <p style="text-align:justify;color:black;">
    We also offer digital printing plates that will be customized and used for your order. For shorter or larger orders, we have your back. We can also show you custom samples. The choice of packaging and box material will be yours. We are always designing your custom-branded packaging, where boxes are designed with a 3d design tool.

</p>



<h3 style="font-size:18px;color:black;">Order a Free Sample Custom Box For Free</h3>
 <p style="text-align:justify;color:black;">
     We at My Box Printing <a href="{{url('get-quote').'/'}}" style="color:#86C542;">offer free sample</a> so that you have the surety for the quality you will be receiving. The satisfaction matters the most. 

     </p>



 <p style="text-align:justify;color:black;">
Our sample is free of cost and always delivers the quality more than expected. This can be the best way to test the box by putting the product in the box.

     </p>
 

<h3 style="font-size:18px;color:black;">Free Logo Design</h3>
 <p style="text-align:justify;color:black;">
    Free sounds quite exciting; it feels like a deal. Yes, we understand the market trends in various industries. And we know what logos are in trend and are more attractive.

</p>
 <p style="text-align:justify;color:black;">
    Just let us know your product, brand name, and some description, and you will get a beautiful logo that will speak much about your brand power.

</p>

<h3 style="font-size:18px;color:black;">
Custom Sizes

</h3>


 <p style="text-align:justify;color:black;">
    We are always ahead in customization of sizes and deliver beautiful custom boxes and packaging.  Custom box sizes are not only perfect for your brand but also for shipping with durability.

</p>

 <p style="text-align:justify;color:black;">
     For perfect packaging, dimensions play a vital role.  So, create packaging that is according to your demand!

     </p>
     
     <h3 style="font-size:18px;color:black;">
         Perfect Mailer Boxes For Every Industry

         </h3>
         
 <p style="text-align:justify;color:black;">
    Mailer boxes are trendy and in demand because they deliver the product safely.

</p>
 <p style="text-align:justify;color:black;">
    Be the sturdy custom shipper in the sea of shipment world, where the choice for packaging is entirely on demand.

</p>

     <h3 style="font-size:18px;color:black;">
    Pricing? Unbeatable

</h3>

 <p style="text-align:justify;color:black;">
    We assure you that we offer the best prices in town. Yes, you read it right. We offer the best retail and wholesale prices that won't let you go away with our price packages.

</p>


     <h3 style="font-size:18px;color:black;">
    No Minimum Quantity

</h3>
 <p style="text-align:justify;color:black;">
    You can order according to your business. Big or small, there is no MOQ requirement.

</p>

    <h3 style="font-size:18px;color:black;">
Free & Fast Delivery Across The US!
</h3>

 <p style="text-align:justify;color:black;">
    We design your box and deliver it to you at your earliest. Once you place an order, it will be delivered to you within 10 to 15 business days. According to your geographical location, we aim to transit in the shortest time with ZERO hidden charges.

</p>


   <h3 style="font-size:18px;color:black;">
Customer Satisfaction Above All!
</h3>

 <p style="text-align:justify;color:black;">
     We believe customer satisfaction is the priority, so we never compromise on that. We offer you support 24/7.

     </p>
<p style="text-align:justify;color:black;">
    So, we are just a call away. <a style="color:#86C442;" href="{{url('contact-us')}}">Contact us</a> and place your order NOW! Also, grab the best deals with free logo design services.

    </p>

   <h2 style="font-size:22px;color:black;">
    
    Final Note

</h2>

<p style="text-align:justify;color:black;">
    The packaging industry is very vast and also customizable. We at My Box Printing offer an unbeatable experience for your brand’s attractive profile. We not only customize the packaging but also give free logo services and custom printing.

</p>

<p style="text-align:justify;color:black;">
  <center><b><i>  “Every box has a different story to tell, and we make sure the box speaks for itself.”</i></b></center>

</p>


<p style="text-align:justify;color:black;">
    We create the best packaging in any custom material or design according to your product.

</p>


<p style="text-align:justify;color:black;">
   <b> Packaging is the backbone of a product</b> to give an experience to remember. My Box Printing is an exceptional packaging company that meets all your expectations and expands your business sales 5x or even more.

</p>

<p style="text-align:justify;color:black;">
    A great unboxing experience makes the customer visit again and again. Get an instant quotation for your business.

</p>

  
 </div>
 
  </div>
  <a class="moreless-button" href="#section" style="color:#86C342;">Read more</a>
</div>


             
        
                </div>

                </div>
            </div>
        </div>
    </div>
</section>

<section class="cp-services-area pb-20 p-relative z-index-1 mt--20 pt-20 hide-on-mobile">
            
            <div class="container">
            <div class="row align-items-end">
            
            
            <div class="cp-cta2-title mb-0 pt-20">
            <p style="font-size: 30px; color: rgb(33 37 41 / var(--tw-text-opacity,1));    font-weight: 600;">Latest News <span class="separator-text" style="font-weight: 100;">Discover custom box packaging ideas, products, and much more.</span>
            <span class="" style="  font-size:17px;  font-weight: 600;color:#86C342;">
            <a href="{{url('blog').'/'}}">View All &gt;</a>
            </span>
            </p>
            </div>
            
            </div></div>
            
            </section>
  <section class="cp-news-area white-bg pt-10 pb-10">
      
      <div class="container">
          <div class="row">
               @foreach($our_blogs as $bb)
              <div class="col-xl-4 col-lg-4 ">
             
            
                  <article>
                       <div class="cp-news-item mb-40">
                           <div class="cp-news-img fix p-relative w-img">
                              <div class="cp-img-overlay "></div>
                              <a href="{{ url(str_replace(' ', '-', strtolower('blog'.'/'.$bb->t_slug))) . '/' }}">
                    <img src="{{ url('images/blog/' . $bb->t_featured_image) }}" style="border-radius:20px;" alt="{{$bb->alt}}">
                    </a>
                           </div>
                           <div class="cp-news-content">
                              
                              <h3 class="cp-news2-title mb-25" style="font-size:20px;">
                              <a href="{{ url(str_replace(' ', '-', strtolower('blog'.'/'.$bb->t_slug))) . '/' }}"> {{$bb->t_title}}</a></h3>
                              <div class="cp-news-btn-share d-flex align-items-center justify-content-between">
                                 <div class="cp-news-btn line-height1">
                                    <a style="color:#A1CD68;" href="{{ url(str_replace(' ', '-', strtolower('blog'.'/'.$bb->t_slug))) . '/' }}" class="cp-border-btn">
                                       Continue Reading
                                      
                                    </a>
                                 </div>
                                 
                              </div>
                           </div>
                        </div>
                     </article>
        
              </div>
                  @endforeach
          </div>
      </div>
      
      
  </section>
                            
            <section class="cp-services-area p-relative z-index-1 hide-on-mobile " >
         
         <div class="container">
            <div class="row align-items-end">
                
                  
<div class="cp-cta2-title mb-0 pt-20" >
 <p style="font-size: 30px; color: rgb(33 37 41 / var(--tw-text-opacity,1)); margin-top: -10px;    font-weight: 600;">FAQ's <span class="separator-text" style="font-weight: 100;">Explore answers to some relevant questions about creating a custom box. If you've any query, feel free to <a href="{{url('contact-us/').'/'}}" style="color:#85C241;">contact us!</a></span>
                <span class="" style="  font-size:17px;  font-weight: 600;color:#86C342;">
              
                </span>
              </p>
</div>

</div></div>

 </section> 
 
                           
<section class="cp-faq-area p-relative pt-20 pb-20 fix hide-on-mobile ">
  <div class="container">
    <div class="row">
        
   
             
                      <div class="col-md-6">
                 
                 
                         <div class="accordion-item" >
                              <h2 class="accordion-header" id="headingOne1">
                                 <button class="accordion-button" style="    background-color: #86c442;color:white;" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne1" aria-expanded="true" aria-controls="collapseOne1" style="color:white;">What kind of boxes do you offer?</button>
                              </h2>
                              <div id="collapseOne1" class="accordion-collapse collapse show" aria-labelledby="headingOne1" data-bs-parent="#accordionExample">
                                 <div class="accordion-body">We offer retail boxes, cardboard boxes, cosmetics boxes, CBD boxes, food and beverages, display boxes, eco-friendly, and much more.</div>
                              </div>
                           </div>
                           
                           
                            
          
</div>

  <div class="col-md-6">

 <div class="accordion-item" >
                              <h2 class="accordion-header" id="headingOne">
                                 <button class="accordion-button" style="    background-color: #86c442;color:white;" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">Do you customize the box designs and shapes?</button>
                              </h2>
                              <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                 <div class="accordion-body">Yes, we do customize on demand, also size and design can be customized according to your demand. Standard or custom boxes can both be provided.</div>
                              </div>
                           </div>
                           
                           
                           
 


</div>


  <div class="col-md-6">


 <div class="accordion-item "  style="visibility: visible; animation-delay: 0.4s;">
  <h2 class="accordion-header" id="headingTwo111">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo111" aria-expanded="false" aria-controls="collapseTwo111">What is the MOQ of the order?
 </button>
  </h2>
  <div id="collapseTwo111" class="accordion-collapse collapse" aria-labelledby="headingTwo111" data-bs-parent="#accordionExample1">
    <div class="accordion-body">
      <p style="text-algin:justify;">We do not have any minimum order requirement; you can order only 100 boxes if you have a small business, and the order can be 10000 or more. We will provide you with the best packaging quality boxes at your doorstep.</p>
    </div>
  </div>
</div>
</div>

  <div class="col-md-6">
      
 <div class="accordion-item "  style="visibility: visible; animation-delay: 0.4s;">
  <h2 class="accordion-header" id="headingTwo1111">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo1111" aria-expanded="false" aria-controls="collapseTwo1111">What is the timeline for delivering the order?
 </button>
  </h2>
  <div id="collapseTwo1111" class="accordion-collapse collapse" aria-labelledby="headingTwo1111" data-bs-parent="#accordionExample11">
    <div class="accordion-body">
      <p style="text-algin:justify;">We usually need 5 to 10 business days for an order to ship to your doorstep; however, that can be an exception for urgent cases.
</p>
    </div>
  </div>
</div>
</div>

  <div class="col-md-6">

 <div class="accordion-item "  style="visibility: visible; animation-delay: 0.4s;">
  <h2 class="accordion-header" id="headingTwo1111w">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo1111w" aria-expanded="false" aria-controls="collapseTwo1111w">How urgent can you deliver? Will it affect quality?

 </button>
  </h2>
  <div id="collapseTwo1111w" class="accordion-collapse collapse" aria-labelledby="headingTwo1111w" data-bs-parent="#accordionExample11w">
    <div class="accordion-body">
      <p style="text-algin:justify;">We try to make it as soon as possible according to the timeline provided, but that doesn’t mean we'll compromise on quality. We will give you the best quality in town to make your brand shine and stand out in the market.

</p>
    </div>
  </div>
</div>
</div>

  <div class="col-md-6">

 <div class="accordion-item "  style="visibility: visible; animation-delay: 0.4s;">
  <h2 class="accordion-header" id="headingTwo11111">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo11111" aria-expanded="false" aria-controls="collapseTwo11111">Do you offer design assistance?
 </button>
  </h2>
  <div id="collapseTwo11111" class="accordion-collapse collapse" aria-labelledby="headingTwo11111" data-bs-parent="#accordionExample11111">
    <div class="accordion-body">
      <p style="text-algin:justify;">Yes, we do offer free logo design for our customers. Being in the market for decades has given us an insight into trending packaging designs, so do not worry. We've got your back.

</p>
    </div>
  </div>
</div>
</div>

  <div class="col-md-6">
<div class="accordion-item "  style="visibility: visible; animation-delay: 0.4s;">
  <h2 class="accordion-header" id="headingTwo111111">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo111111" aria-expanded="false" aria-controls="collapseTwo111111">Can we give our design?

 </button>
  </h2>
  <div id="collapseTwo111111" class="accordion-collapse collapse" aria-labelledby="headingTwo111111" data-bs-parent="#accordionExample11111">
    <div class="accordion-body">
      <p style="text-algin:justify;">Yes, we welcome suggestions and your design; if it needs amendments, we can also work on that.


</p>
    </div>
  </div>
</div>

</div>

  <div class="col-md-6">


<div class="accordion-item "  style="visibility: visible; animation-delay: 0.4s;">
  <h2 class="accordion-header" id="headingTwo1111111">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo1111111" aria-expanded="false" aria-controls="collapseTwo1111111">Do you offer printing services as well?


 </button>
  </h2>
  <div id="collapseTwo1111111" class="accordion-collapse collapse" aria-labelledby="headingTwo1111111" data-bs-parent="#accordionExample1111111">
    <div class="accordion-body">
      <p style="text-algin:justify;">Yes, we are providing the best packaging with premium printing in one place.  We can do printing on your packaging boxes as well.


</p>
    </div>
  </div>
</div>

</div>
  <div class="col-md-6">
<div class="accordion-item "  style="visibility: visible; animation-delay: 0.4s;">
  <h2 class="accordion-header" id="headingTwo1111111x">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo1111111x" aria-expanded="false" aria-controls="collapseTwo1111111x"> Is there a fixed niche for customization in boxes?

 </button>
  </h2>
  <div id="collapseTwo1111111x" class="accordion-collapse collapse" aria-labelledby="headingTwo1111111x" data-bs-parent="#accordionExample1111111x">
    <div class="accordion-body">
      <p style="text-algin:justify;">No, you can get any box customized. having vast experience, makes us proudly say that we work with perfection on all grounds.



</p>
    </div>
  </div>
</div>

        </div>
          <div class="col-md-6">
              <div class="accordion-item "  style="visibility: visible; animation-delay: 0.4s;">
  <h2 class="accordion-header" id="headingTwo1111111xx">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo1111111xx" aria-expanded="false" aria-controls="collapseTwo1111111xx">How can I see the material that will be used?


 </button>
  </h2>
  <div id="collapseTwo1111111xx" class="accordion-collapse collapse" aria-labelledby="headingTwo1111111xx" data-bs-parent="#accordionExample1111111xx">
    <div class="accordion-body">
      <p style="text-algin:justify;">On our website, there is a wide range of material information. you can check materials from our website, but if you have something in mind in terms of the selection of some specific material, we can do that as well.




</p>
    </div>
  </div>
</div>



       
         </div>
              
              
                 <div class="col-md-6">
              <div class="accordion-item "  style="visibility: visible; animation-delay: 0.4s;">
  <h2 class="accordion-header" id="headingTwo1111111xx1">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo1111111xx1" aria-expanded="false" aria-controls="collapseTwo1111111xx1">Do you provide a sample?


 </button>
  </h2>
  <div id="collapseTwo1111111xx1" class="accordion-collapse collapse" aria-labelledby="headingTwo1111111xx1" data-bs-parent="#accordionExample1111111xx1">
    <div class="accordion-body">
      <p style="text-algin:justify;">Yes, we do provide any kind of sample you want for your brand packaging.




</p>
    </div>
  </div>
</div>



       
         </div>
              
              
              
              
                     
                 <div class="col-md-6">
              <div class="accordion-item "  style="visibility: visible; animation-delay: 0.4s;">
  <h2 class="accordion-header" id="headingTwo1111111xx11">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo1111111xx11" aria-expanded="false" aria-controls="collapseTwo1111111xx11">How to contact your team for more details?



 </button>
  </h2>
  <div id="collapseTwo1111111xx11" class="accordion-collapse collapse" aria-labelledby="headingTwo1111111xx11" data-bs-parent="#accordionExample1111111xx11">
    <div class="accordion-body">
      <p style="text-algin:justify;">You may email us at support@myboxprinting.com or call 847-200-0974




</p>
    </div>
  </div>
</div>



       
         </div>
         
         
         
           
               
               
            </div>
         </div>
      </section>
 
      <!-- testimonial area end here  -->

      <!-- news area end here  -->
 
       

   </main>

   
   @include('frontend/footer')
 
<script>
   var swiper = new Swiper('.cp-testimonial2-active', {
      slidesPerView: 2, // Show two testimonials per row
      spaceBetween: 30,
      loop:true,
      navigation: {
         nextEl: '.cp-testimonial2-button-next',
         prevEl: '.cp-testimonial2-button-prev',
      },
      breakpoints: {
         768: { slidesPerView: 2 }, // 2 slides on tablets and above
         576: { slidesPerView: 1 }  // 1 slide on small screens
      }
   });
</script>

      