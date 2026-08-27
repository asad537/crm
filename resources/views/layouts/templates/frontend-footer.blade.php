@php
      $dynamic_page =  DB::table('dynamic_pages')->get();
      $our_socials  = DB::table('socials_media')->get();
@endphp
 
 <div class="footer pt-2 pt-sm-2 position-relative" >
 
    <div class="content_wrapper py-2 pt-sm-5 pb-4">
        <div class="container px-lg-0">
            <div class="row">
                <div class="col-sm-6 col-md-6 col-lg-4 col-xl-4 footer-block">
                    <div class="pb-footer-item">
                        <div class="pb-footer-title">
                       <img src="{{url('footer_logo.webp')}}" alt="footer">
                        </div>
                        <p style="font-size: 12px;"> 
                      If You Have Any Questions Or Require Fur- ther Assistance, Please Contact Our Customer Service Team Between 8.00am And 7.00pm CST, Monday-Friday

                       </p>
                        <div class="footer-widget-area">
                            <div class="widget-contact">
                                <ul class="contact-info list-style-type-none">
                                    <li>
                                        <a href="#" class="d-flex">
                                            <i class="fas fa-map-marker-alt mr-sm-2" aria-hidden="true"></i>
                                            <span class="anchor-span" style="font-size: 12px;"> 9933 Franklin Ave,
Suite # 112
Franklin Park, IL 60131, United States
</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="tel:+1 800 369 2755">
                                            <i class="fas fa-phone mr-sm-2"  aria-hidden="true"></i>
                                            <span class="anchor-span" style="font-size: 12px;"> 847-200-0974</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="mailto: support@myboxprinting.com?subject=feedback">
                                            <i class="fas fa-envelope mr-sm-2" aria-hidden="true"></i>
                                            <span class="anchor-span" style="font-size: 12px;"> support@myboxprinting.com</span>
                                        </a>
                                    </li>
                                   
                        
                                </ul>
                            </div> 
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-4 col-xl-2 footer-block">
                    <div class="pb-footer-item">
                        <div class="pb-footer-title">
                          Useful Links
                        </div>
                        <ul class="footer_list_view">
                           
                              
                            
                            @foreach ($dynamic_page as $item)
                                <li>
                                    <a href="{{ url(str_replace(' ', '-', strtolower($item->page_url))).'/'}}" title="{{$item->page_name}}">{{$item->page_name}}</a>
                                </li>
                            @endforeach
                            <!--<li>-->
                            <!--    <a href="{{url('beat-my-price').'/'}}" title="Beat my Price">Beat my Price</a>-->
                            <!--</li>-->
                            <li>
                                <a href="{{url('get-quote/').'/'}}" title="Get a Quote">Get a Quote</a>
                            </li>
                            <li>
                                <a href="{{url('contact-us').'/'}}" title="Contact US">Contact US</a>
                            </li>
                            <li>
                                <a href="{{url('sitemap.xml')}}" title="Beat my Price">Sitemap</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-4 col-md-6 col-lg-4 col-xl-2 footer-block">
                    <div class="pb-footer-item">
                        <div class="pb-footer-title">
                           Categories
                        </div>

                        <ul class="footer_list_view">
                            <?php $new_arrivals= DB::table('categories')->orderBy('id', 'desc')->limit(8)->get();?>

                            @foreach($new_arrivals as $new_arrivals)
                            <li><a style="display:block;" class="" href="{{url($new_arrivals->cate_url).'/'}}">{{$new_arrivals->cate_name}}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-sm-8 col-md-6 col-lg-12 col-xl-4 footer-block">
                    <div class="pb-footer-item">
                        <div class="pb-footer-title">
                            newsletter
                        </div>
                          <div class="footer-menu">
                            <div class="copyright-text" style="font-size: 12px;"><p>Subscribe to our newsletter to get helpful industry news and the latest packaging ideas!</p></div>
                            <form class="form-inline justify-content-start mb-2 mb-sm-5 my-lg-0" action="{{url('email_subcribe').'/'}}" method="post">
                                @csrf
                                <input class="form-control"style="height:auto" type="text" name="email_subcribe" placeholder="Enter your Email here" aria-label="" required />
                                <button class="btn pb_btn_footer btn-footer mt-xl-0" type="submit" name="submit">Subscribe</button>
                            </form>
                        </div>
                        <ul class="social_media_icons">
                            <li class="my-3">
                               
                                <a href="#" aria-label="Read more about" target="_blank">
                                <img title="" alt="google" style="border: 0;height:25px;" src="{{url('public/google-review.png')}}" class="lazy" />
                                  </a>   
                                   <a href="#" aria-label="Read more about" target="_blank">
                                    <img src="{{url('public/BBB.png')}}" alt="DMCA" style="border-radius: 9px;height:25px;" />
                                    </a>
                                  <a href="#" target="_blank" aria-label="Read more about">
                                    <img src="{{url('public/Trust-pilot_1.png')}}" alt="SSL" style="border-radius: 9px;height:25px;" />
                                </a>
                            </li>
                        </ul>
                        <ul class="gcp-footer-nav mt-sm-3 mt-4">
                        <li class="footer-icons-social bg-social-whi">
  <?php $socials_media=DB::table('socials_media')->first();?>
  
                                @if(!empty($socials_media->facebook_link))
                                  <a href="{{ $socials_media->facebook_link }}" rel="noopener noreferrer" aria-label="Read more about" class="social-icon social-facebook social facebook" target="_blank" title="facebook"><i class="fab fa-facebook-f top-nav-icon" aria-hidden="true"></i></a>
                                @endif
                                @if(!empty($socials_media->twitter_link))
                                  <a href="{{ $socials_media->twitter_link }}" rel="noopener noreferrer" aria-label="Read more about" class="social-icon social-twitter social twitter" target="_blank" title="Twitter"><i class="fab fa-twitter top-nav-icon" aria-hidden="true"></i></a>
                                @endif
                                @if(!empty($socials_media->youtube_link))
                                  <a href="{{ $socials_media->youtube_link }}" rel="noopener noreferrer" aria-label="Read more about" class="social-icon social-youtube social youtube" target="_blank" title="youtube"><i class="fab fa-youtube top-nav-icon" aria-hidden="true"></i></a>
                                @endif
                                @if(!empty($socials_media->instagram_link))
                                  <a href="{{ $socials_media->instagram_link }}" rel="noopener noreferrer" aria-label="Read more about" class="social-icon social-instagram social instagram" target="_blank" title="instagram"><i class="fab fa-instagram top-nav-icon" aria-hidden="true"></i></a>
                                @endif
                                @if(!empty($socials_media->pinterest_link))
                                  <a href="{{ $socials_media->pinterest_link }}" rel="noopener noreferrer" aria-label="Read more about" class="social-icon social-pinterest social pinterest" target="_blank" title="pinterest"><i class="fab fa-pinterest-p top-nav-icon" aria-hidden="true"></i></a>
                                @endif
                                @if(!empty($socials_media->linkedin_link))
                                  <a href="{{ $socials_media->linkedin_link }}" rel="noopener noreferrer" aria-label="Read more about" class="social google social-icon social-linkedin" target="_blank" title="linkedin"><i class="fab fa-linkedin" aria-hidden="true"></i></a>
                                @endif
                                  <a href="https://myboxprintingusa.tumblr.com/" rel="noopener noreferrer" aria-label="Read more about" class="social google social-icon social-linkedin" target="_blank" title="tumblr"><i class="fab fa-tumblr-square" aria-hidden="true"></i></a>
                       </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
       
    </div>

    <div class="La_copyright">
        <div class="container">
            <div class="row">
                <div class="col-sm-12 col-md-12 text-left">
                    <p class="La_foot_text">Copyright 2024 © <span class="color-primary"> All Rights Reserved</span>. Designed by <span class="color-primary">MyBoxPrinting</span></p>
                </div>
            </div>
        </div>
    </div>
</div>

    <style>
            .smt-app {
                position: relative
            }

            .smt-app .sm-fixed {
                z-index: 100000000000 !important;
                position: fixed
            }

            .smt-app,.smt-app * {
                letter-spacing: normal;
                font-size: 14px;
                line-height: normal;
                font-weight: normal;
                margin: 0
            }

            .smt-app img {
                user-select: none
            }

            .smt-wrapper {
                background: none;
                padding: 0;
                width: auto;
                height: auto
            }

            .hover-opacity {
                cursor: pointer;
                transition: opacity 200ms linear
            }

            .hover-opacity:hover {
                opacity: 0.83 !important
            }

            .powered-link {
                font-size: 14px !important;
                color: #555;
                text-decoration: none;
                margin-top: 5px;
                display: inline-block;
                white-space: nowrap
            }

            .powered-link img {
                width: 14px !important;
                height: 14px !important
            }

            .icon-wrapper+.powered-link {
                position: absolute;
                left: 50%;
                transform: translateX(-55%) !important
            }

            .sm-disable-animation {
                animation-duration: 0ms !important
            }

            .sm-close {
                position: absolute;
                color: #fff;
                top: 12px;
                right: 12px;
                width: 24px;
                height: 24px;
                font-size: 22px;
                cursor: pointer;
                text-align: center;
                line-height: 24px;
                z-index: 1;
                font-style: normal
            }

            @keyframes sm-slide-show {
                0% {
                    transform: translateZ(-1400px) translateY(20px);
                    opacity: 0
                }

                100% {
                    transform: translateZ(0) translateY(0);
                    opacity: 1
                }
            }

            @keyframes sm-slide-hide {
                100% {
                    transform: translateZ(-1400px) translateY(20px);
                    opacity: 0
                }

                0% {
                    transform: translateZ(0) translateY(0);
                    opacity: 1
                }
            }

            @keyframes sm-slide-show-centered {
                0% {
                    transform: translateZ(-1400px) translateY(20px) translateX(-50%);
                    opacity: 0
                }

                100% {
                    transform: translateZ(0) translateY(0) translateX(-50%);
                    opacity: 1
                }
            }

            @keyframes sm-slide-hide-centered {
                100% {
                    transform: translateZ(-1400px) translateY(20px) translateX(-50%);
                    opacity: 0
                }

                0% {
                    transform: translateZ(0) translateY(0) translateX(-50%);
                    opacity: 1
                }
            }

            .smt-app-whatsapp {
                font-family: sf-pro-display, Roboto, sans-serif
            }

            .smt-app-whatsapp .sm-box {
                box-shadow: 5px 0px 10px rgba(0,0,0,0.2);
                width: 380px;
                border-radius: 6px;
                background: #fff;
                display: flex !important;
                flex-direction: column;
                max-height: calc(100% - 100px);
                transition: height 200ms cubic-bezier(0.25, 0.46, 0.45, 0.94)
            }

            .smt-app-whatsapp .sm-box.show.top-center,.smt-app-whatsapp .sm-box.show.bottom-center,.smt-app-whatsapp .sm-box.show.bottom,.smt-app-whatsapp .sm-box.show.top {
                animation: sm-slide-show-centered 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp .sm-box.show:not(.top-center):not(.bottom-center):not(.bottom):not(.top) {
                animation: sm-slide-show 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp .sm-box.closing.top-center,.smt-app-whatsapp .sm-box.closing.bottom-center,.smt-app-whatsapp .sm-box.closing.bottom,.smt-app-whatsapp .sm-box.closing.top {
                animation: sm-slide-hide-centered 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp .sm-box.closing:not(.top-center):not(.bottom-center):not(.bottom):not(.top) {
                animation: sm-slide-hide 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp .sm-box:not(.show):not(.closing) {
                display: none !important
            }

            .smt-app-whatsapp .sm-box.top-left,.smt-app-whatsapp .sm-box.top-center,.smt-app-whatsapp .sm-box.top,.smt-app-whatsapp .sm-box.top-right {
                top: 80px
            }

            .smt-app-whatsapp .sm-box.top-left.button-circle_bubble,.smt-app-whatsapp .sm-box.top-center.button-circle_bubble,.smt-app-whatsapp .sm-box.top.button-circle_bubble,.smt-app-whatsapp .sm-box.top-right.button-circle_bubble {
                top: 100px
            }

            .smt-app-whatsapp .sm-box.top-left.button-tab,.smt-app-whatsapp .sm-box.top-center.button-tab,.smt-app-whatsapp .sm-box.top.button-tab,.smt-app-whatsapp .sm-box.top-right.button-tab {
                top: 60px
            }

            .smt-app-whatsapp .sm-box.bottom-left,.smt-app-whatsapp .sm-box.bottom-center,.smt-app-whatsapp .sm-box.bottom,.smt-app-whatsapp .sm-box.bottom-right {
                bottom: 80px
            }

            .smt-app-whatsapp .sm-box.bottom-left.button-circle_bubble,.smt-app-whatsapp .sm-box.bottom-center.button-circle_bubble,.smt-app-whatsapp .sm-box.bottom.button-circle_bubble,.smt-app-whatsapp .sm-box.bottom-right.button-circle_bubble {
                bottom: 100px
            }

            .smt-app-whatsapp .sm-box.bottom-left.button-tab,.smt-app-whatsapp .sm-box.bottom-center.button-tab,.smt-app-whatsapp .sm-box.bottom.button-tab,.smt-app-whatsapp .sm-box.bottom-right.button-tab {
                bottom: 60px
            }

            .smt-app-whatsapp .sm-box.bottom-left,.smt-app-whatsapp .sm-box.left,.smt-app-whatsapp .sm-box.top-left {
                left: 30px
            }

            .smt-app-whatsapp .sm-box.bottom-right,.smt-app-whatsapp .sm-box.right,.smt-app-whatsapp .sm-box.top-right {
                right: 30px
            }

            .smt-app-whatsapp .sm-box.bottom-center,.smt-app-whatsapp .sm-box.top-center {
                left: 50%;
                right: initial;
                transform: translateX(-50%)
            }

            .smt-app-whatsapp .sm-box::before {
                content: '';
                position: absolute;
                width: 13px;
                height: 13px;
                transform: rotate(45deg);
                background: #fff
            }

            .smt-app-whatsapp .sm-box.top-left::before,.smt-app-whatsapp .sm-box.top-right::before,.smt-app-whatsapp .sm-box.top-center::before {
                top: -6px
            }

            .smt-app-whatsapp .sm-box.bottom-left::before,.smt-app-whatsapp .sm-box.bottom-right::before,.smt-app-whatsapp .sm-box.bottom-center::before {
                bottom: -6px
            }

            .smt-app-whatsapp .sm-box.button-circle_bubble.top-left::before,.smt-app-whatsapp .sm-box.button-circle_bubble.bottom-left::before {
                left: 20px
            }

            .smt-app-whatsapp .sm-box.button-circle_bubble.top-right::before,.smt-app-whatsapp .sm-box.button-circle_bubble.bottom-right::before {
                right: 20px
            }

            .smt-app-whatsapp .sm-box:not(.button-circle_bubble).top-left::before,.smt-app-whatsapp .sm-box:not(.button-circle_bubble).bottom-left::before {
                left: 10px
            }

            .smt-app-whatsapp .sm-box:not(.button-circle_bubble).top-right::before,.smt-app-whatsapp .sm-box:not(.button-circle_bubble).bottom-right::before {
                right: 10px
            }

            .smt-app-whatsapp .sm-box.bottom-center::before,.smt-app-whatsapp .sm-box.top-center::before {
                left: 50%;
                transform: rotate(45deg) translateX(-50%)
            }

            .smt-app-whatsapp .sm-box.bottom-center::before {
                bottom: -8px
            }

            .smt-app-whatsapp .sm-box.top-center::before {
                top: 0
            }

            .smt-app-whatsapp .sm-box .sm-box-header {
                color: #fff;
                padding: 24px 36px;
                position: relative;
                border-top-left-radius: 6px;
                border-top-right-radius: 6px;
                box-sizing: border-box
            }

            .smt-app-whatsapp .sm-box .sm-box-header a {
                color: #fff;
                text-decoration: underline !important
            }

            .smt-app-whatsapp .sm-box .sm-box-header::before {
                border-top-left-radius: 6px;
                border-top-right-radius: 6px;
                content: '';
                position: absolute;
                top: 0;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, transparent 0%, rgba(0,0,0,0.2) 100%)
            }

            .smt-app-whatsapp .sm-box .sm-box-header .sm-box-title,.smt-app-whatsapp .sm-box .sm-box-header .sm-box-subtitle {
                position: relative;
                z-index: 5;
                color: #fff
            }

            .smt-app-whatsapp .sm-box .sm-box-header .sm-box-title {
                font-size: 24px;
                line-height: 1.2;
                margin-bottom: 8px
            }

            .smt-app-whatsapp .sm-box .sm-box-header .sm-box-subtitle {
                line-height: 1.75;
                font-size: 16px
            }

            .smt-app-whatsapp .sm-box .sm-box-body {
                box-sizing: border-box;
                overflow-x: hidden;
                overflow-y: auto;
                background: #f5f5f5;
                padding-bottom: 50px;
                position: relative;
                height: 100%
            }

            .smt-app-whatsapp .sm-box .sm-box-footer {
                position: relative;
                color: #303030;
                background-color: #fff;
                border-top: 1px solid #e6e6e6;
                border-bottom-left-radius: 6px;
                border-bottom-right-radius: 6px;
                overflow: hidden;
                box-sizing: border-box;
                min-height: 56px;
                display: flex;
                justify-content: center;
                flex-direction: column
            }

            .smt-app-whatsapp .sm-box .sm-box-footer .sm-box-footer-info {
                padding: 12px 36px;
                width: 100%;
                font-size: 12px;
                align-items: center;
                justify-content: center;
                text-align: center;
                box-sizing: border-box !important
            }

            .smt-app-whatsapp .sm-box .sm-box-footer a {
                color: #000;
                text-decoration: none
            }

            .smt-app-whatsapp.force-mobile .sm-box {
                width: 100% !important;
                min-width: 100% !important;
                max-height: 100% !important;
                height: 100% !important;
                left: 0 !important;
                right: 0 !important;
                margin: 0 !important;
                top: 0 !important;
                bottom: 0 !important
            }

            .smt-app-whatsapp.force-mobile .sm-box.show {
                animation: sm-slide-show 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp.force-mobile .sm-box.closing {
                animation: sm-slide-hide 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp.force-mobile .sm-box,.smt-app-whatsapp.force-mobile .sm-box .sm-box-header,.smt-app-whatsapp.force-mobile .sm-box .sm-box-header::before,.smt-app-whatsapp.force-mobile .sm-box.sm-box-footer {
                border-radius: 0 !important
            }

            .smt-app-whatsapp.force-mobile .sm-box::before {
                display: none
            }

            .smt-app-whatsapp.force-mobile .sm-button:not(.sm-button-circle).bottom-center {
                min-width: max-content
            }

            .smt-app-whatsapp .sm-button {
                box-shadow: 0 1px 6px 0 rgba(0,0,0,0.06),0 2px 32px 0 rgba(0,0,0,0.16);
                box-sizing: border-box;
                padding: 0;
                display: block
            }

            .smt-app-whatsapp .sm-button.top-left,.smt-app-whatsapp .sm-button.bottom-left,.smt-app-whatsapp .sm-button.left {
                left: 25px
            }

            .smt-app-whatsapp .sm-button.top-right,.smt-app-whatsapp .sm-button.bottom-right,.smt-app-whatsapp .sm-button.right {
                
            }

            .smt-app-whatsapp .sm-button.left,.smt-app-whatsapp .sm-button.right {
                transform: translateY(-50%);
                top: 50%
            }

            .smt-app-whatsapp .sm-button.left .powered-link,.smt-app-whatsapp .sm-button.right .powered-link {
                bottom: -25px
            }

            .smt-app-whatsapp .sm-button.top-left,.smt-app-whatsapp .sm-button.top-right,.smt-app-whatsapp .sm-button.top,.smt-app-whatsapp .sm-button.top-center {
                top: 25px
            }

            .smt-app-whatsapp .sm-button.top-left .powered-link,.smt-app-whatsapp .sm-button.top-right .powered-link,.smt-app-whatsapp .sm-button.top .powered-link,.smt-app-whatsapp .sm-button.top-center .powered-link {
                top: -25px
            }

            .smt-app-whatsapp .sm-button.bottom-left,.smt-app-whatsapp .sm-button.bottom-right,.smt-app-whatsapp .sm-button.bottom,.smt-app-whatsapp .sm-button.bottom-center {
                bottom: 25px
            }

            .smt-app-whatsapp .sm-button.bottom-left .powered-link,.smt-app-whatsapp .sm-button.bottom-right .powered-link,.smt-app-whatsapp .sm-button.bottom .powered-link,.smt-app-whatsapp .sm-button.bottom-center .powered-link {
                bottom: -20px
            }

            .smt-app-whatsapp .sm-button.top-center,.smt-app-whatsapp .sm-button.bottom-center,.smt-app-whatsapp .sm-button.top,.smt-app-whatsapp .sm-button.bottom {
                left: 50%;
                transform: translateX(-50%);
                right: initial
            }

            .smt-app-whatsapp .sm-button a:not(.powered-link) {
                text-decoration: none
            }

            .smt-app-whatsapp .sm-button.pressed {
                opacity: 1 !important
            }

            .smt-app-whatsapp .sm-button.pressed.sm-button-text {
                padding: 6px !important
            }

            .smt-app-whatsapp .sm-button.pressed.sm-button-tab {
                padding-left: 6px !important;
                padding-right: 6px !important
            }

            .smt-app-whatsapp .sm-button.pressed img,.smt-app-whatsapp .sm-button.pressed i {
                animation: sm-rotate-animation cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.3s
            }

            .smt-app-whatsapp .sm-button.pressed::before {
                z-index: 2;
                content: '';
                position: absolute;
                top: 0;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, transparent 0%, rgba(0,0,0,0.4) 100%);
                cursor: pointer
            }

            .smt-app-whatsapp .sm-button.pressed::before:not(.button-square_text) {
                border-top-left-radius: 6px;
                border-top-right-radius: 6px
            }

            .smt-app-whatsapp .sm-button.sm-button-text.sm-rounded.pressed::before {
                border-radius: 40px
            }

            .smt-app-whatsapp .sm-button.sm-button-text,.smt-app-whatsapp .sm-button.sm-button-tab {
                display: flex;
                white-space: nowrap;
                color: #fff;
                user-select: none
            }

            .smt-app-whatsapp .sm-button.sm-button-text:not(.sm-small),.smt-app-whatsapp .sm-button.sm-button-tab:not(.sm-small) {
                padding: 6px 12px
            }

            .smt-app-whatsapp .sm-button.sm-button-text:not(.sm-small) span,.smt-app-whatsapp .sm-button.sm-button-tab:not(.sm-small) span {
                font-size: 16px;
                line-height: 28px
            }

            .smt-app-whatsapp .sm-button.sm-button-text:not(.sm-small) img,.smt-app-whatsapp .sm-button.sm-button-tab:not(.sm-small) img {
                width: 30px;
                height: 30px
            }

            .smt-app-whatsapp .sm-button.sm-button-text.sm-small,.smt-app-whatsapp .sm-button.sm-button-tab.sm-small {
                padding: 3px 6px;
                display: flex;
                align-items: center
            }

            .smt-app-whatsapp .sm-button.sm-button-text.sm-small span,.smt-app-whatsapp .sm-button.sm-button-tab.sm-small span {
                font-size: 13px;
                line-height: normal
            }

            .smt-app-whatsapp .sm-button.sm-button-text.sm-small img,.smt-app-whatsapp .sm-button.sm-button-tab.sm-small img {
                width: 26px;
                height: 26px
            }

            .smt-app-whatsapp .sm-button.sm-button-text.sm-rounded,.smt-app-whatsapp .sm-button.sm-button-tab.sm-rounded {
                border-radius: 40px
            }

            .smt-app-whatsapp .sm-button.sm-button-text a:not(.powered-link),.smt-app-whatsapp .sm-button.sm-button-tab a:not(.powered-link) {
                display: inherit;
                color: #fff !important
            }

            .smt-app-whatsapp .sm-button.sm-button-tab {
                border-radius: 10px
            }

            .smt-app-whatsapp .sm-button.sm-button-tab.pressed::before {
                border-radius: 10px
            }

            .smt-app-whatsapp .sm-button.sm-button-tab.top-left,.smt-app-whatsapp .sm-button.sm-button-tab.top-center,.smt-app-whatsapp .sm-button.sm-button-tab.top-right {
                top: -5px;
                padding-top: 12px
            }

            .smt-app-whatsapp .sm-button.sm-button-tab.bottom-left,.smt-app-whatsapp .sm-button.sm-button-tab.bottom-center,.smt-app-whatsapp .sm-button.sm-button-tab.bottom-right {
                bottom: -5px;
                padding-bottom: 12px
            }

            .smt-app-whatsapp .sm-button.sm-button-tab.left {
                transform: translateY(-50%) translateX(-50%) rotate(-90deg);
                left: 0;
                padding-top: 50px
            }

            .smt-app-whatsapp .sm-button.sm-button-tab.right {
                transform: translateY(-50%) translateX(50%) rotate(-90deg);
                right: 0;
                padding-bottom: 50px
            }

            .smt-app-whatsapp .sm-button.sm-button-circle:not(.sm-small),.smt-app-whatsapp .sm-button.sm-button-circle:not(.sm-small) a:not(.powered-link) {
                height: 60px;
                width: 60px
            }

            .smt-app-whatsapp .sm-button.sm-button-circle.sm-small,.smt-app-whatsapp .sm-button.sm-button-circle.sm-small a:not(.powered-link) {
                height: 50px;
                width: 50px
            }

            .smt-app-whatsapp .sm-button.sm-button-circle,.smt-app-whatsapp .sm-button.sm-button-circle a:not(.powered-link) {
                justify-content: center;
                align-items: center;
                display: flex
            }

            .smt-app-whatsapp .sm-button.sm-button-circle,.smt-app-whatsapp .sm-button.sm-button-circle.pressed:before {
                border-radius: 50%
            }

            .smt-app-whatsapp .sm-button.sm-button-circle:not(.pressed) img:not(.emoji) {
                height: 100% !important;
                width: 100% !important;
                display: inline !important;
                min-height: auto !important
            }

            .smt-app-whatsapp .sm-button .powered-link {
                display: block;
                position: absolute;
                left: 50%;
                transform: translateX(-55%)
            }

            .smt-app-whatsapp .sm-button .sm-red-dot {
                position: absolute;
                right: 2px;
                top: 2px;
                background: red;
                width: 10px;
                height: 10px;
                border-radius: 50%
            }

            .smt-app-whatsapp .sm-shake img,.smt-app-whatsapp .sm-shake i {
                animation: sm-shake-animation linear 1.5s infinite
            }

            @keyframes sm-shake-animation {
                0% {
                    transform: rotate(0) scale(1) skew(0.017rad)
                }

                25% {
                    transform: rotate(0) scale(1) skew(0.017rad)
                }

                35% {
                    transform: rotate(-0.3rad) scale(1) skew(0.017rad)
                }

                45% {
                    transform: rotate(0.3rad) scale(1) skew(0.017rad)
                }

                55% {
                    transform: rotate(-0.3rad) scale(1) skew(0.017rad)
                }

                65% {
                    transform: rotate(0.3rad) scale(1) skew(0.017rad)
                }

                75% {
                    transform: rotate(0) scale(1) skew(0.017rad)
                }

                100% {
                    transform: rotate(0) scale(1) skew(0.017rad)
                }
            }

            @keyframes sm-rotate-animation {
                0% {
                    transform: rotate(0)
                }

                100% {
                    transform: rotate(180deg)
                }
            }

            .smt-app-whatsapp .message {
                color: black;
                font-size: 14px;
                background: white;
                box-shadow: 5px 0px 10px rgba(0,0,0,0.5);
                border-radius: 3px;
                max-width: 400px;
                min-width: 50px;
                max-height: 40px;
                padding: 5px 10px;
                white-space: nowrap;
                text-overflow: ellipsis;
                overflow: hidden;
                margin-top: 15px !important;
                transition: opacity 300ms linear;
                text-align: center
            }

            .smt-app-whatsapp .message.show.top-center,.smt-app-whatsapp .message.show.bottom-center,.smt-app-whatsapp .message.show.bottom,.smt-app-whatsapp .message.show.top {
                animation: sm-slide-show-centered 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp .message.show:not(.top-center):not(.bottom-center):not(.bottom):not(.top) {
                animation: sm-slide-show 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp .message.closing.top-center,.smt-app-whatsapp .message.closing.bottom-center,.smt-app-whatsapp .message.closing.bottom,.smt-app-whatsapp .message.closing.top {
                animation: sm-slide-hide-centered 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp .message.closing:not(.top-center):not(.bottom-center):not(.bottom):not(.top) {
                animation: sm-slide-hide 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp .message:not(.show):not(.closing) {
                display: none !important
            }

            .smt-app-whatsapp .message.top-left,.smt-app-whatsapp .message.bottom-left {
                left: 105px !important
            }

            .smt-app-whatsapp .message.top-left,.smt-app-whatsapp .message.top-right {
                top: 30px
            }

            .smt-app-whatsapp .message.top-right,.smt-app-whatsapp .message.bottom-right {
                right: 105px !important
            }

            .smt-app-whatsapp .message.bottom-left,.smt-app-whatsapp .message.bottom-right {
                bottom: 40px
            }

            .smt-app-whatsapp .message.top,.smt-app-whatsapp .message.top-center {
                top: 85px
            }

            .smt-app-whatsapp .message.bottom,.smt-app-whatsapp .message.bottom-center {
                bottom: 100px
            }

            .smt-app-whatsapp .message.top-center,.smt-app-whatsapp .message.bottom-center,.smt-app-whatsapp .message.top,.smt-app-whatsapp .message.bottom {
                left: 50%;
                right: initial
            }

            .smt-app-whatsapp .message.left,.smt-app-whatsapp .message.right {
                top: calc(50% - 13px);
                bottom: initial;
                margin: 0 !important
            }

            .smt-app-whatsapp .message.left {
                left: 110px
            }

            .smt-app-whatsapp .message.right {
                right: 110px
            }

            .smt-app-whatsapp .sm-box {
                z-index: 100000000050 !important
            }

            .smt-app-whatsapp .sm-box.one-manager .sm-selected-manager .sm-manager {
                flex-direction: row-reverse
            }

            .smt-app-whatsapp .sm-box.one-manager .sm-message {
                float: left !important
            }

            .smt-app-whatsapp .sm-box.one-manager .sm-message::before {
                left: -5px;
                right: unset
            }

            .smt-app-whatsapp .sm-box .sm-box-body.manager-mode {
                min-height: 200px;
                background-color: #e6ddd4
            }

            .smt-app-whatsapp .sm-box .sm-box-body.manager-mode::before {
                display: block;
                position: absolute;
                content: "";
                left: 0px;
                top: 0px;
                height: 100%;
                width: 100%;
                z-index: 0;
                opacity: 0.08
            }

            .smt-app-whatsapp .sm-box .sm-selected-manager {
                display: flex;
                justify-content: space-between
            }

            .smt-app-whatsapp .sm-box .sm-selected-manager .sm-name {
                margin-top: 5px
            }

            .smt-app-whatsapp .sm-box .sm-selected-manager .sm-name,.smt-app-whatsapp .sm-box .sm-selected-manager .sm-role {
                color: #fff
            }

            .smt-app-whatsapp .sm-box .sm-selected-manager .sm-avatar::before {
                border-color: #085e54 !important
            }

            .smt-app-whatsapp .sm-box .sm-chat-back img {
                width: 16px;
                height: 16px;
                transform: rotate(180deg);
                margin-top: 22px;
                cursor: pointer;
                opacity: 1
            }

            .smt-app-whatsapp .sm-box .sm-chat-back img:hover {
                opacity: 0.8
            }

            .smt-app-whatsapp .sm-box .sm-box-footer input.sm-chat-message-input {
                color: #333;
                direction: ltr;
                box-sizing: border-box;
                outline: none;
                font-size: 16px;
                line-height: 20px;
                width: calc(100% - 80px);
                border: none;
                height: auto !important;
                margin: 0 18px
            }

            .smt-app-whatsapp .sm-box .sm-box-footer input.sm-chat-message-input::-webkit-input-placeholder {
                color: #999
            }

            .smt-app-whatsapp .sm-box .sm-box-footer input.sm-chat-message-input:-moz-placeholder {
                color: #999
            }

            .smt-app-whatsapp .sm-box .sm-box-footer .sm-chat-send {
                position: absolute;
                z-index: 10;
                right: 25px;
                top: 18px
            }

            .smt-app-whatsapp .sm-box .sm-box-footer .sm-chat-send img {
                width: 20px;
                height: 20px
            }

            .smt-app-whatsapp .sm-box .sm-box-footer .sm-chat-button {
                display: flex;
                white-space: nowrap;
                justify-content: center;
                background: #075e54;
                border-radius: 6px;
                padding: 10px 0;
                box-sizing: border-box;
                width: calc(100% - 20px);
                margin: 0 10px
            }

            .smt-app-whatsapp .sm-box .sm-box-footer .sm-chat-button img {
                width: 16px;
                height: 16px
            }

            .smt-app-whatsapp .sm-box .sm-box-footer .sm-chat-button span {
                margin-left: 10px;
                color: #fff
            }

            .smt-app-whatsapp .initial-message {
                background: white;
                box-shadow: 5px 0px 10px rgba(0,0,0,0.5);
                border-radius: 6px;
                width: 400px;
                min-width: 50px;
                padding: 6px 12px;
                text-align: left;
                max-width: calc(100% - 20px);
                transition: background-color 150ms cubic-bezier(0.25, 0.46, 0.45, 0.94)
            }

            .smt-app-whatsapp .initial-message.show.top-center,.smt-app-whatsapp .initial-message.show.bottom-center,.smt-app-whatsapp .initial-message.show.bottom,.smt-app-whatsapp .initial-message.show.top {
                animation: sm-slide-show-centered 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp .initial-message.show:not(.top-center):not(.bottom-center):not(.bottom):not(.top) {
                animation: sm-slide-show 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp .initial-message.closing.top-center,.smt-app-whatsapp .initial-message.closing.bottom-center,.smt-app-whatsapp .initial-message.closing.bottom,.smt-app-whatsapp .initial-message.closing.top {
                animation: sm-slide-hide-centered 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp .initial-message.closing:not(.top-center):not(.bottom-center):not(.bottom):not(.top) {
                animation: sm-slide-hide 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp .initial-message:not(.show):not(.closing) {
                display: none !important
            }

            .smt-app-whatsapp .initial-message.top-left,.smt-app-whatsapp .initial-message.top-center,.smt-app-whatsapp .initial-message.top,.smt-app-whatsapp .initial-message.top-right {
                top: 80px
            }

            .smt-app-whatsapp .initial-message.top-left.button-circle_bubble,.smt-app-whatsapp .initial-message.top-center.button-circle_bubble,.smt-app-whatsapp .initial-message.top.button-circle_bubble,.smt-app-whatsapp .initial-message.top-right.button-circle_bubble {
                top: 100px
            }

            .smt-app-whatsapp .initial-message.top-left.button-tab,.smt-app-whatsapp .initial-message.top-center.button-tab,.smt-app-whatsapp .initial-message.top.button-tab,.smt-app-whatsapp .initial-message.top-right.button-tab {
                top: 60px
            }

            .smt-app-whatsapp .initial-message.bottom-left,.smt-app-whatsapp .initial-message.bottom-center,.smt-app-whatsapp .initial-message.bottom,.smt-app-whatsapp .initial-message.bottom-right {
                bottom: 80px
            }

            .smt-app-whatsapp .initial-message.bottom-left.button-circle_bubble,.smt-app-whatsapp .initial-message.bottom-center.button-circle_bubble,.smt-app-whatsapp .initial-message.bottom.button-circle_bubble,.smt-app-whatsapp .initial-message.bottom-right.button-circle_bubble {
                bottom: 100px
            }

            .smt-app-whatsapp .initial-message.bottom-left.button-tab,.smt-app-whatsapp .initial-message.bottom-center.button-tab,.smt-app-whatsapp .initial-message.bottom.button-tab,.smt-app-whatsapp .initial-message.bottom-right.button-tab {
                bottom: 60px
            }

            .smt-app-whatsapp .initial-message.bottom-left,.smt-app-whatsapp .initial-message.left,.smt-app-whatsapp .initial-message.top-left {
                left: 30px
            }

            .smt-app-whatsapp .initial-message.bottom-left.left,.smt-app-whatsapp .initial-message.left.left,.smt-app-whatsapp .initial-message.top-left.left {
                left: 110px
            }

            .smt-app-whatsapp .initial-message.bottom-left.left .button-tab,.smt-app-whatsapp .initial-message.left.left .button-tab,.smt-app-whatsapp .initial-message.top-left.left .button-tab {
                left: 80px
            }

            .smt-app-whatsapp .initial-message.bottom-right,.smt-app-whatsapp .initial-message.right,.smt-app-whatsapp .initial-message.top-right {
                right: 30px
            }

            .smt-app-whatsapp .initial-message.bottom-right.right,.smt-app-whatsapp .initial-message.right.right,.smt-app-whatsapp .initial-message.top-right.right {
                right: 110px
            }

            .smt-app-whatsapp .initial-message.bottom-right.right .button-tab,.smt-app-whatsapp .initial-message.right.right .button-tab,.smt-app-whatsapp .initial-message.top-right.right .button-tab {
                right: 80px
            }

            .smt-app-whatsapp .initial-message.top-center,.smt-app-whatsapp .initial-message.bottom-center,.smt-app-whatsapp .initial-message.top,.smt-app-whatsapp .initial-message.bottom {
                left: 50%;
                right: initial
            }

            .smt-app-whatsapp .initial-message.left,.smt-app-whatsapp .initial-message.right {
                top: calc(50% - 35px);
                bottom: initial;
                margin: 0 !important
            }

            .smt-app-whatsapp .initial-message .sm-avatar {
                width: 40px !important;
                height: 40px !important;
                min-width: 40px !important;
                margin-top: 4px
            }

            .smt-app-whatsapp .initial-message .sm-avatar::before {
                width: 7px !important;
                height: 7px !important
            }

            .smt-app-whatsapp .initial-message .sm-role {
                font-size: 10px !important
            }

            .smt-app-whatsapp .initial-message .sm-message {
                float: none;
                padding: 0;
                box-shadow: none;
                margin: 4px 0;
                background: none
            }

            .smt-app-whatsapp .initial-message .sm-message::before {
                display: none !important
            }

            .smt-app-whatsapp .initial-message .sm-close {
                color: #000;
                top: 5px;
                right: 5px
            }

            .smt-app-whatsapp .initial-message:hover {
                cursor: pointer;
                background-color: #f1f1f1
            }

            .smt-app-whatsapp .force-mobile .initial-message:not(.top-center):not(.bottom-center):not(.top):not(.bottom) {
                left: 10px !important;
                right: unset !important
            }

            .smt-app-whatsapp .sm-manager {
                display: flex;
                border-top: 1px solid #e9e9e9;
                padding: 16px 36px;
                cursor: pointer;
                transition: all 200ms linear;
                position: relative;
                z-index: 5
            }

            .smt-app-whatsapp .sm-manager.sm-manager-header {
                border: none;
                pointer-events: none;
                padding: 0
            }

            .smt-app-whatsapp .sm-manager:hover {
                background: rgba(0,0,0,0.05)
            }

            .smt-app-whatsapp .sm-manager .sm-avatar {
                width: 60px;
                height: 60px;
                min-width: 60px;
                position: relative;
                background-size: cover;
                border-radius: 50%
            }

            .smt-app-whatsapp .sm-manager .sm-avatar::before {
                content: '';
                display: block;
                width: 10px;
                height: 10px;
                position: absolute;
                bottom: 4px;
                right: 4px;
                background-color: #4ad504;
                z-index: 4;
                border: 1px solid #f5f5f5;
                border-radius: 50%
            }

            .smt-app-whatsapp .sm-manager .sm-info {
                margin-top: 4px;
                padding: 0 16px;
                overflow: hidden;
                color: #161c2d
            }

            .smt-app-whatsapp .sm-manager .sm-info .sm-role,.smt-app-whatsapp .sm-manager .sm-info .sm-caption {
                font-size: 12px;
                opacity: .6
            }

            .smt-app-whatsapp .sm-manager .sm-info .sm-name {
                font-weight: 700;
                line-height: 1.5em
            }

            .smt-app-whatsapp .sm-manager .sm-info .sm-name,.smt-app-whatsapp .sm-manager .sm-info .sm-role,.smt-app-whatsapp .sm-manager .sm-info .sm-caption {
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis
            }

            .smt-app-whatsapp .sm-message {
                float: right;
                position: relative;
                background: #fff;
                padding: 10px 16px;
                margin: 20px;
                border-radius: 4px;
                box-shadow: rgba(0,0,0,0.13) 0px 1px 0.5px;
                -webkit-animation: sm-slide-show 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
                animation: sm-slide-show 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) both
            }

            .smt-app-whatsapp .sm-message:not(.preloader)::before {
                background: #fff;
                content: '';
                position: absolute;
                top: 12px;
                right: -5px;
                width: 10px;
                height: 10px;
                transform: rotate(45deg)
            }

            .smt-app-whatsapp .sm-message img {
                height: 5px
            }

            .smt-app-whatsapp .sm-message .message-author {
                font-size: 13px;
                font-weight: 700;
                line-height: 18px;
                color: rgba(0,0,0,0.4);
                margin-bottom: 4px
            }

            .smt-app-whatsapp .sm-message .message-text {
                color: #161c2d;
                font-size: 14px;
                line-height: 19px
            }

            .smt-app-whatsapp .sm-message .message-time {
                margin-top: 4px;
                font-size: 12px;
                line-height: 16px;
                color: rgba(17,17,17,0.5)
            }

            .smt-app-whatsapp .sm-button.sm-button-text,.smt-app-whatsapp .sm-button.sm-button-tab {
                background: rgb(84 66 52);
                color: #fff
            }

            .smt-app-whatsapp .sm-box.top-left::before,.smt-app-whatsapp .sm-box.top-right::before,.smt-app-whatsapp .sm-box.top-center::before {
                background: #09554c
            }

            .smt-app-whatsapp .sm-box .sm-box-header {
                background: #085e54
            }

            .smt-app-whatsapp .sm-box .sm-selected-manager .sm-avatar::before {
                border-color: #085e54 !important
            }

            .smt-app-whatsapp .sm-box .sm-box-body.manager-mode::before {
                background-size: cover;
                background-image: url(https://app.smartarget.online/assets/appsAssets/whatsapp_background.webp)
            }

            .smt-app-whatsapp .sm-box .sm-box-footer a {
                color: #075e54
            }

            .smt-app-whatsapp .sm-box .sm-box-footer input.sm-message-input {
                color: #333;
                direction: ltr;
                box-sizing: border-box;
                outline: none;
                font-size: 16px;
                line-height: 20px;
                width: calc(100% - 80px);
                border: none;
                height: auto !important;
                margin: 0 18px
            }

            .smt-app-whatsapp .sm-box .sm-box-footer input.sm-message-input::-webkit-input-placeholder {
                color: #999
            }

            .smt-app-whatsapp .sm-box .sm-box-footer input.sm-message-input:-moz-placeholder {
                color: #999
            }

            .smt-app-whatsapp .sm-box .sm-box-footer .sm-send {
                position: absolute;
                z-index: 10;
                right: 25px;
                top: 18px
            }

            .smt-app-whatsapp .sm-box .sm-box-footer .sm-send img {
                width: 20px;
                height: 20px
            }

            .smt-app-whatsapp .sm-box .sm-box-footer .sm-chat-button {
                background: #075e54
            }
        </style>
        <style>
            .waves-whatsapp {
                animation: waves-whatsapp linear 3s infinite;
            }

            @keyframes waves-whatsapp {
                0% {
                    box-shadow: 0 8px 10px rgba(48, 191, 57, 0.3), 0 0 0 0 rgba(48, 191, 57, 0.2), 0 0 0 0 rgba(48, 191, 57, 0.2)
                }

                40% {
                    box-shadow: 0 8px 10px rgba(48, 191, 57, 0.3), 0 0 0 35px rgba(48, 191, 57, 0.2), 0 0 0 0 rgba(48, 191, 57, 0.2)
                }

                80% {
                    box-shadow: 0 8px 10px rgba(48, 191, 57, 0.3), 0 0 0 55px rgba(48, 191, 57, 0), 0 0 0 26.7px rgba(48, 191, 57, 0.067)
                }

                100% {
                    box-shadow: 0 8px 10px rgba(48, 191, 57, 0.3), 0 0 0 80px rgba(48, 191, 57, 0), 0 0 0 40px rgba(48, 191, 57, 0.0)
                }
            }
        </style>
        <div class="smt-app smt-app-whatsapp force-desktop">
            <div class="waves-whatsapp sm-shake bottom-right   sm-fixed hover-opacity sm-button sm-button-text sm-rounded" style="background-color: rgb(84 66 52);">
                <a href="https://wa.me/16308850045" target="_blank">
                    <img alt="" src="{{url('whatsapp.svg')}}">
                    <span>&nbsp;</span>
                    <span style="color: rgb(255, 255, 255);">Any questions? Inquire on Whatsapp</span>
                    <span>&nbsp;&nbsp;</span>
                    <div class="sm-red-dot"></div>
                </a>
            </div>
        </div>
    <div class="fixed-button-position-right mblof">
 
        <div class="button-transform-position-right" >
            <div class="button-shadow-border-right" >
                <div id="myBtn" class="button-box-sizing-right " style="border-radius: 4px !important" data-toggle="modal" data-target="#myModal">Get Quote</div>
            </div>
        </div>
    </div>
    
    <div class="modal fade " id="myModal" tabindex="-1" role="dialog" >
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">  Get a Quote </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">
            <form action="{{url('callback-email').'/'}}" method="Post" id="call">
              @csrf
              <div class="form-group">
                <label for="recipient-name" class="col-form-label">Name:</label>
                <input type="text" name="name" class="form-control" placeholder="Enter your name:" required="">
              </div>
              <div class="form-group">
                <label for="message-text" class="col-form-label">Email:</label>
                  <input type="text" name="email"  id="search_input_two"  class="form-control" placeholder="Enter your Email:" required="">
              </div>
                  <div class="form-group">
                <label for="message-text" class="col-form-label">Contact Number:</label>
                  <input type="text" name="phone" class="form-control" placeholder="Enter your Contact Number:" required="">
              </div>
               <div class="form-group">
                <label for="message-text" class="col-form-label">Message:</label>
                <textarea id="w3review" name="msg" rows="4" cols="50">
                    
                </textarea>
              </div>
              <div class="form-group">
                @include('frontend.partials.math-captcha')
                <div id="callback-captcha-error" style="color: #dc3545; font-size: 14px; font-weight: bold; margin-top: 10px; display: none; padding: 12px; background: #f8d7da; border-radius: 5px;">
                    ⚠️ Please complete the reCAPTCHA verification before submitting
                </div>
              </div>
               <button type="submit" class="btn btn-success" name="submit" style="background-color: #c4a283;">Submit</button>
          </form>
          
          <script>
          var callbackCaptchaCompleted = false;
          
          function onCallbackCaptchaSuccess(token) {
              callbackCaptchaCompleted = true;
              var errorDiv = document.getElementById('callback-captcha-error');
              if (errorDiv) {
                  errorDiv.style.display = 'none';
              }
          }
          
          (function() {
              function initCallbackFormValidation() {
                  var form = document.getElementById('call');
                  var errorDiv = document.getElementById('callback-captcha-error');
                  
                  if (!form) return;
                  
                  form.onsubmit = function(event) {
                      if (!callbackCaptchaCompleted) {
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
                              
                              callbackCaptchaCompleted = false;
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
                  document.addEventListener('DOMContentLoaded', initCallbackFormValidation);
              } else {
                  initCallbackFormValidation();
              }
          })();
          </script>
      </div>
    </div>
  </div>
</div>
