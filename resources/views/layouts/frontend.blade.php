<!DOCTYPE html>
<html lang="en-us">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        
        <link  rel="stylesheet" href="{{url('assets/css/animate.css')}}"   />
        <link  rel="stylesheet" href="{{url('assets/css/bootstrap.min.css')}}"  />
        <link  rel="stylesheet" href="{{url('assets/css/stylesheet1.css')}}"   />
        <link  rel="stylesheet" href="{{url('assets/css/jquery.smartmenus.bootstrap-4.css')}}"  />
        <link  rel="stylesheet" href="{{url('assets/css/owl.carousel.min.css')}}"   />
        <link rel="icon" type="image/svg+xml" sizes="any" href="{{ url('favicon-square.svg') }}" />
        <link rel="shortcut icon" href="{{ url('favicon-square.svg') }}" />
        <title><?php echo $meta_title; ?></title>
        <meta name="description" content="<?php echo $meta_description; ?>">
        <meta name="keywords" content="<?php echo $meta_tags; ?>">
        
        
<link rel="alternate" hreflang="en-us" href="<?php echo request()->is('/') ? rtrim(url('/'), '/') : url()->current().'/'; ?>" />
       <link rel="alternate" hreflang="x-default" href="<?php echo request()->is('/') ? rtrim(url('/'), '/') : url()->current().'/'; ?>"  />
   
   
   
        <?php
            $currentUrl = str_replace('/index.php', '', url()->current());
            if (request()->is('/') || $currentUrl === rtrim(url('/'), '/')) {
                $canonicalUrl = rtrim(url('/'), '/');
            } elseif (substr(strtolower($currentUrl), -5) === '.html') {
                $canonicalUrl = rtrim($currentUrl, '/');
            } else {
                $canonicalUrl = rtrim($currentUrl, '/') . '/';
            }
        ?>
        <link rel="canonical" href="<?php echo $canonicalUrl; ?>"/>
        <meta name="author" content="SW-THEMES" />
     
@if(request()->has('page'))
         <meta name="robots" content="noindex, follow">
     @else
         <meta name="robots" content="index, follow">
     @endif
 
    
    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "VideoObject",
  "name": "MyBoxPrinting",
  "description": "Custom Printed Boxes: The #1 Custom Boxes & Packaging USA",
  "thumbnailUrl": "https://www.youtube.com/channel/UC7uhry_F39i9bTf5RKVf2Og",
  "uploadDate": "2022-05-25",
  "duration": "PT1M15S"
}
</script>
<script type="application/ld+json">

                    {
                        "@context": "https://schema.org",
                        "@type":"Organization",
                        "name": "MyBoxPrinting",
                        "url": "https://www.myboxprinting.com/",
                        "logo": "https://www.myboxprinting.com/printing.webp",
                        "address": {
                        "@type": "PostalAddress",
                        "streetAddress": "9933 Franklin Ave Suite 112 Franklin park IL 60131",
                        "addressLocality": "9933 Franklin Ave Suite 112",
                        "addressRegion": "Franklin",
                        "postalCode": "60131",
                        "addressCountry": "USA"
                        },
                        "contactPoint": {
                        "@type": "ContactPoint",
                        "contactType": "contact",
                        "telephone": "847-200-0974",
                        "email": "support@myboxprinting.com"},
                        "sameAs":[
    "https://www.facebook.com/myboxprintingofficial/",
    "https://twitter.com/myboxprintingus",
    "https://www.youtube.com/channel/UC7uhry_F39i9bTf5RKVf2Og",
    "https://www.pinterest.com/myboxprintingus/",
    "https://www.linkedin.com/company/myboxprinting",
    "https://myboxprintingusa.tumblr.com/"
  ]
                    }  
</script>





    
<!-- twitter Card -->
<?php if(!empty($twitter_card)): ?>
 
 <?php echo $twitter_card; ?>
 
 <?php endif; ?> 
 <!-- OPen Graph -->
 <?php if(!empty($open_graph)): ?>
 
 <?php echo $open_graph; ?>
 
 <?php endif; ?> 
 
    <!-- SERP FEATURE Start-->
<?php if(!empty($BreadcrumbList)): ?>
<script type="application/ld+json">
<?php echo $BreadcrumbList; ?>
</script>
<?php endif; ?> 
<!-- SERP FEATURE End-->
 <!-- FAQ's code -->
 <?php if(!empty($faq_schema)): ?>
<script type="application/ld+json">
<?php echo $faq_schema; ?>
  
</script>
<?php endif; ?> 
<!-- Rating Code -->
<?php if(!empty($rating_code)): ?>
<script type="application/ld+json">
<?php echo $rating_code; ?>
  
</script>
<?php endif; ?> 
<!-- Business Listing Code -->
<?php if(!empty($business_listing)): ?>
<script type="application/ld+json">
<?php echo $business_listing; ?>
  
</script>
<?php endif; ?> 
<!-- Blog Detail Page Code -->
<?php if(!empty($blog_detail_page)): ?>
<script type="application/ld+json">
<?php echo $blog_detail_page; ?>
  
</script>
<?php endif; ?> 
 
<?php if(!empty($BreadcrumbList)): ?>
<script type="application/ld+json">
<?php echo $BreadcrumbList; ?>
  
</script>
<?php endif; ?>
    </head>

    <body>      

@if (Session::has('message'))

@endif
 

        @include('layouts.templates.frontend-navbar')
 

    <main>
<h2 class="data-pop"></h2>
  <style>
 


          #ddexitpopwrapper {
    display: flex;
    position: fixed;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    z-index: 1000;
		pointer-events: none;
    align-items: center;
    justify-content: center;
}

#ddexitpopwrapper .veil{
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    position: fixed;
    background-color: rgba(0,0,0,.7);
    content: "";
    z-index: 1;
    display: none;
    cursor: default;
}


.ddexitpop { 
    width: 90%;
    max-width: 700px;
    border: 2px solid black;
    padding: 10px;
    z-index: 2;
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box;
    position: relative;
    border: 20px solid black;
    background: white;
    left: 0;
    top: -100px;
		-webkit-animation-duration: .5s; 
		animation-duration: .5s; 
    visibility: hidden;
}



.ddexitpop .calltoaction{ 
	display: inline-block;
    text-decoration: none;
    border-radius: 5px;
    padding: 15px;
    background: #15C5FF;
    display: block;
    width: 80%;
    font: bold 24px Arial;
    box-shadow: 0 0 15px gray, 0 0 10px gray inset;
    margin: 10px auto;
    text-align: center;
    color: white !important;
}


div.closeexitpop{ 
	width: 70px;
	height: 70px;
	overflow: hidden;
	display: none;
	position: fixed;
	cursor: pointer;
	text-indent: -1000px;
	z-index: 3;
	top: 10px;
	right: 10px;
}


#ddexitpopwrapper.open{
	pointer-events: auto;
}

#ddexitpopwrapper.open .veil{ 
    display: block;
}

#ddexitpopwrapper.open div.closeexitpop{
    display: block;
}

#ddexitpopwrapper.open .ddexitpop{ 
    visibility: visible;
}



@media screen and (max-height: 765px){
	.ddexitpop{
		top: 0;
	}
}



        </style>
    <style>
            @media (min-width: 576px)
   {
        .modal-exit {
            max-width: 430px;
            margin: 5rem auto;
        }
    }
    .modal-exit {box-shadow: 0 19px 38px rgba(0,0,0,0.30), 0 15px 12px rgba(0,0,0,0.22);}
    .modal-body-legacy
    {
      max-height:100%;
      padding: 0px;
    }
        </style>
          @yield('content')

  
    </main>

   

        @include('layouts.templates.frontend-footer')
 
    

        <script   src="{{ URL::asset('assets/js/jquery-2.2.4.min.js') }}"></script>
        <script   src="{{ URL::asset('assets/js/popper.min.js') }}"></script>
        <script   src="{{ URL::asset('assets/js/bootstrap.min.js') }}"></script>
        <script   src="{{  URL::asset('assets/js/aos.js') }}"></script>
              
        <script   src="{{  URL::asset('assets/js/owl.carousel.min.js') }}"></script>
       <script    src="{{ URL::asset('assets/js/fontawesome.js')}}"></script>
        
        <script   type="text/javascript" src="{{ URL::asset('assets/js/jquery.smartmenus.js') }}"></script>

        <script   type="text/javascript" src="{{ URL::asset('assets/js/jquery.smartmenus.bootstrap-4.js') }}"></script>

       <script   type="text/javascript" src="{{ URL::asset('assets/js/load.js') }}" ></script>
        <script   type="text/javascript" src="{{ URL::asset('assets/js/loadpopup.js') }}" ></script>

   
        <script>
            $(document).ready(function() {
              function close_accordion_section() {
                  $('.accordion .accordion-section-title').removeClass('active');
                  $('.accordion .accordion-section-content').slideUp(300).removeClass('open');
              }

              $('.accordion-section-title').click(function(e) {
                  // Grab current anchor value
                  var currentAttrValue = $(this).attr('href');

                  if($(e.target).is('.active')) {
                      close_accordion_section();
                  }else {
                      close_accordion_section();

                      // Add active class to section title
                      $(this).addClass('active');
                      // Open up the hidden content panel
                      $('.accordion ' + currentAttrValue).slideDown(300).addClass('open');
                  }

                  e.preventDefault();
              });
          });

        </script>
        @yield('scripts')
        
 
    
       <script src="{{url('/assets/js/main.js')}}"></script> 
<script id="ze-snippet" src="https://static.zdassets.com/ekr/snippet.js?key=f3a32a9c-7305-49a5-b7fe-d15b22476662"> </script>
   
   
        <script>
          $(document).on("click", ".contact-close-btn", function(){
            $(".alert-msg .modal").removeClass("show");
            $(".alert-msg .modal").css("display", "");
          });
        </script>
        <!--zopim-->


    <script>
        var recaptcha_response = '';
        
        
        function submitUserForm21() {
            if(recaptcha_response.length == 0) {
                document.getElementById('g-recaptcha-error2').innerHTML = '<span style="color:red;">This field is required.</span>';
                return false;
            }
            return true;
        }
        
        function verifyCaptcha11(token) {
            recaptcha_response = token;
            document.getElementById('g-recaptcha-error2').innerHTML = '';
        }
    </script>
    

    
       <script>
        var recaptcha_response = '';
        
        
        function submitUserForm2() {
            if(recaptcha_response.length == 0) {
                document.getElementById('g-recaptcha-error2').innerHTML = '<span style="color:red;">This field is required.</span>';
                return false;
            }
            return true;
        }
        
        function verifyCaptcha2(token) {
            recaptcha_response = token;
            document.getElementById('g-recaptcha-error2').innerHTML = '';
        }
    </script>
        <script>
        var recaptcha_response = '';
        function submitUserForm3() {
            if(recaptcha_response.length == 0) {
                document.getElementById('g-recaptcha-error3').innerHTML = '<span style="color:red;">This field is required.</span>';
                return false;
            }
            return true;
        }
        
        function verifyCaptcha3(token) {
            recaptcha_response = token;
            document.getElementById('g-recaptcha-error3').innerHTML = '';
        }
    </script>
   
   

    
    </body>
</html>
