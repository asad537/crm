 <?php 
    
        $why_tcb2  =  DB::table('dynamic_pages')->where('page_url', 'why-tcb.php')->get();
        $clients_feedback  = DB::table('blogs')->orderBy('t_id', 'desc')->limit(2)->get();

    ?>
    <div id="top-header-bar" class="px-3">
        <div class="container-fluid px-lg-5 ">
            <div class="row">
                <div class="col-sm-5 col-md-5 col-lg-5 col-xl-6 col-12 align-self-center  pos2-sm-to-md">
                <ul class="gcp-footer-nav top-social-nav dfdfdf">
                            <li class="footer-icons-social ">




                                    <a href="https://www.facebook.com/myboxprintingofficial/" aria-label="Read more about" title"facebook"="" target="_blank" class="social-icon social-facebook social facebook"><i class="fab fa-facebook-f top-nav-icon" aria-hidden="true"></i></a>
                                    <a href="https://twitter.com/myboxprintingus" aria-label="Read more about" title"twitter"="" target="_blank" class="social-icon social-twitter social twitter"><i class="fab fa-twitter top-nav-icon" aria-hidden="true"></i></a>
                                    <a href="https://www.pinterest.com/myboxprintingus/"  aria-label="Read more about" title"pinterest"="" target="_blank" class="social-icon social-pinterest social pinterest"><i class="fab fa-pinterest-p top-nav-icon" aria-hidden="true"></i></a>
                                    <a href="#" aria-label="Read more about"  title"instagram"="" target="_blank" class="social-icon social-instagram social instagram"><i class="fab fa-instagram top-nav-icon" aria-hidden="true"></i></a>
                                    
                                    <a href="https://www.linkedin.com/company/myboxprinting" aria-label="Read more about" title"google="" plus"="" target="_blank" class="social google social-icon social-linkedin"><i class="fab fa-linkedin" aria-hidden="true"></i></a>
                                                            </li>
                        </ul>
                </div>
                <div class="col-sm-7 col-md-7 col-lg-7 col-xl-6 col-12 align-self-center text-lg-right pos-sm-to-md " style=" text-align: center;">
                    
               
                       
                          <a class="icons_view mr-lg-2 mr-md-1 mr-sm-1" href="tel:847-200-0974">
                            <i class="fa fa-phone" aria-hidden="true"></i>   847-200-0974
                        </a>
                        
                            
                            
                        <a class="icons_view mr-lg-5 mr-md-3 mr-sm-3" href="mailto: support@myboxprinting.com">
                            <i class="fa fa-envelope" aria-hidden="true"></i>  support@myboxprinting.com
                        </a>
                     
                        
                </div>
            </div>
        </div>
    </div>

    
    <div class="pakBoxes_main_menu on_mobile_no_show">
        <div class="container-fluid px-lg-5 pt-3 pb-3">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <a class="navbar-brand img-class-brand-1 pt-3" href="{{url('/')}}">
                        <img src="{{url('printing.webp')}}" class="img-fluid pb-hf-logo d-block mx-auto logo-margin"  style="width:220px;" alt="logo" />
                    </a>
                </div>
                <div class="col-md-4">
                   
                    <div class="main">
                        <form action="{{url('search').'/'}}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="input-group" style="border:1px solid #c4a283 !important;border-radius: 5rem;">
                                <input type="search" name="search" class="form-control border-right-0" placeholder="Search" style="background:#eee;border-radius: 5rem 0 0 5rem;border: 1px solid #e8e8e8;height:auto" required />
                                <div class="input-group-append">
                                    <button class="btn btn-secondary  border-left-0 py-sm-2"  aria-label="Name" style="border: none;border-left: 1px solid #c4a283 !important;background:transparent;color: #fff;" > 
                                         <i class="fa-solid fa-search" style="color: #c4a283;" aria-hidden="true"></i>
                                         
                                    </button>
                                </div>
                            </div>
                        </form>
                      
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="float-md-right">
                        <a href="tel:847-200-0974" class="mr-4">
                            <button class="btn btn btn-success custom-btn">Call Now</button>
                        </a>
                        <a href="{{url('get-quote/').'/'}}" class="mr-4">
                            <button class="btn btn btn-success custom-btn">Get Quote</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
   <div class="clearfix"></div>
   
    <header id="pb-sticky-wrapper" class="pb-sticky-wrapper p-0">
        <div id="pb" class="header-body border-top-0">
            <div class="header-container" id="sticky-header">
                <div class="container-fluid">
                    <!-- Navbar -->
                    <nav id="unique-bg" class="p-0 navbar navbar-expand-xl jcb_navigationWrap">
                        
                        <div class="navbar-header">
                            <div class="header-logo on_mobile_show logo-bread">
                                <a href="{{url('/')}}">
                                    <img alt="corrugationboxes" src="{{url('logo.png')}}" alt="corrugationboxes Logo"style="height:55px;"/>
                                </a>
                                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                            </div>
                           
                        </div>

                        <div class="collapse navbar-collapse justify-content-around" id="navbarNavDropdown">
                            <!-- Left nav -->
                            <ul class="nav navbar-nav uniq---ul pt-2 pb-2">
                                <li class="nav-item"><a class="nav-link active-view" href="{{url('/')}}">Home</a></li>
                              
                             @foreach ($parent_category as $mainCate)
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle " href="{{  url(str_replace( ' ','-',strtolower($mainCate->cate_url))).'/'}}">{{ $mainCate->cate_name }}</a>
                                    <ul class="dropdown-menu">
                                        @foreach ($all_subcategory as $subCate)
                                           @if ($subCate->parent_cate == $mainCate->id )       
                                            <li class="dropdown">
                                                <a class="dropdown-item dropdown-toggle" href="{{ url(str_replace( ' ','-',strtolower($subCate->cate_url))).'/'}}">{{$subCate->cate_name}}</a>
                                                <ul class="dropdown-menu mega-dropdown-2">
                                                    @foreach ($all_products as $singleProd)
                                                      @if ($singleProd->prod_category == $subCate->id )
                                                         <li class="mega-dropdown-items">
                                                             <a class="dropdown-item" href="{{ url(str_replace( ' ','-',strtolower($singleProd->prod_url))).'/' }}">
                                                             
                                                                <?php if(strlen($singleProd->prod_name)> 17)
                                                                   {
                                                                       echo substr($singleProd->prod_name,0, 17).'...';
                                                                   }
                                                                   else{
                                                                      echo  $singleProd->prod_name;
                                                                   }
                                                                
                                                                 ?>
                                                             </a>
                                                         </li>
                                                      @endif
                                                    @endforeach
                                                </ul>
                                            </li>
                                           @endif
                                        @endforeach

                                    </ul>
                                </li>
                             @endforeach


<?php $cardboardboxes=DB::table('cardboardboxes')->get();?>
                                <li class="nav-item dropdown"> 
                                    <a class="nav-link dropdown-toggle " href="{{ url('corrugation-display-boxes')}}">Corrugation Display Boxes</a> 
                                    
                                    
                                              <ul class="dropdown-menu">
                                  
                                                
                                                @foreach($cardboardboxes as $prod)
                                                
                                            <li class="dropdown">
                                                <a class="dropdown-item" href="{{ url(str_replace( ' ','-',strtolower($prod->prod_url))).'/' }}">{{$prod->prod_name}}</a>
                                              
                                            </li>
                                        
                                        @endforeach
                                    </ul>
                                    
                                    
                                
                                
                              </li> 
                           
                            
                                
                                
                                <li class="nav-item "><a class="nav-link" href="{{ url('printing-products').'/'}}">printing products</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{url('get-quote/').'/'}}">Request Quote</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ url('contact-us').'/'}}">Contact Us</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ url('blog').'/'}}">Blog</a></li>
                                
                            </ul>

                            
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </header>