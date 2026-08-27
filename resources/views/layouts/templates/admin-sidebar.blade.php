<div class="left_col scroll-view" style="    background: #1f1919 !important;">
    <div class="navbar nav_title" style="border: 0;" style="    background: #1f1919 !important;">
        <a href="{{ url('/dashboard') }}" class="site_title" style="    background: #1f1919 !important;">
            <span>
                <img src="{{ asset('logo.png') }}" style="width:120px;" class="img-fluid" alt="">
            </span>
        </a>
    </div>

    <div class="clearfix"></div>

    <!-- menu profile quick info -->
    <div class="profile clearfix">

    </div>
    <!-- /menu profile quick info -->

    <br />

    <!-- sidebar menu -->
    <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
        <div class="menu_section">
            <h3>General</h3>
            <ul class="nav side-menu">

                <li>
                    <a href="{{ url('/dashboard') }}"><i class="fa fa-home"></i>Dashboard <span
                            class="fa fa-chevron-down"></span></a>

                </li>
                <li>
                    <a><i class="fa fa-rocket"></i> States <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/add-states') }}"> Add States</a></li>
                        <li><a href="{{ url('/show-states/') }}">Show States</a></li>
                        <li><a href="{{ url('/add-states-category') }}"> Add States Category</a></li>
                        <li><a href="{{ url('/show-states-category/') }}">Show States Category</a></li>
                    </ul>
                </li>




                <li>
                    <a><i class="fa fa-rocket"></i> Product FAQ's <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/add-product-faq') }}"> Add Product FAQ's</a></li>
                        <li><a href="{{ url('/show-product-faq/') }}">Show Product FAQ's</a></li>
                    </ul>
                </li>

                <li>
                    <a><i class="fa fa-rocket"></i> PPC Pages <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/add-ppc-pages') }}"> Add PPC Pages</a></li>
                        <li><a href="{{ url('/show_ppc_page/') }}">Show PPC Pages</a></li>
                    </ul>
                </li>




                <li>
                    <a><i class="fa fa-home"></i> Home Slider<span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('home-slider/') }}">add home slider</a></li>
                        <li><a href="{{ url('show-homeslider/') }}">Show home slider </a></li>
                    </ul>
                </li>
                <li>
                    <a>
                        <i class="fa  fa-rocket"></i>Home Page category <span class="fa fa-chevron-down"></span>
                    </a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/add-boxdesign') }}">add category</a></li>
                        <li><a href="{{ url('/show-boxdesign') }}">show category</a></li>
                    </ul>
                </li>


                <li>
                    <a><i class="fa fa-home"></i> Home Content <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('home-content/') }}">add home Content</a></li>
                    </ul>
                </li>
                <li>
                    <a><i class="fa fa-cubes"></i> Popular Products <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('popular-products/edit-product-boxes') }}">Edit Popular Product Page</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a><i class="fa fa-fw fa-rocket"></i> Static Pages <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/add-dynamic-page') }}">add Static Pages</a></li>
                        <li><a href="{{ url('/show-dynamic-page') }}">Show Static Pages </a></li>
                    </ul>
                </li>
                <li>
                    <a><i class="fa fa-file"></i> Add Blog<span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/addblog') }}">add blog</a></li>
                        <li><a href="{{ url('/showblog') }}">Show blog </a></li>
                    </ul>
                </li>
                <li>
                    <a><i class="fa fa-users"></i> Authors<span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/addauthor') }}">Add New Author</a></li>
                        <li><a href="{{ url('/showauthors') }}">Show All Authors</a></li>
                    </ul>
                </li>
                <li>
                <a><i class="fa fa-rocket"></i> Other Product <span class="fa fa-chevron-down"></span></a>
                <ul class="nav child_menu">
                    <li><a href="{{ url('/add-otherproduct') }}"> Add Other Product</a></li>
                    <li><a href="{{ url('/show-otherproduct') }}">Show Other Product</a></li>
                </ul>
                </li>

                <li>
                    <a><i class="fa fa-rocket"></i> Corrugation Display Boxes <span
                            class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/add-cardboard') . '/' }}"> Add Corrugation Display Boxes</a></li>
                        <li><a href="{{ url('/show-cardboard') }}">Show Corrugation Display Boxes</a></li>
                    </ul>
                </li>
                <li>
                    <a>
                        <i class="fa  fa-rocket"></i> add category <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/addcategory') }}">add category</a></li>
                        <li><a href="{{ url('/showcategory') }}">show category</a></li>
                    </ul>
                </li>
                <li>
                    <a><i class="fa fa-pie-chart"></i> Product <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/addproduct') }}"> Add Product</a></li>
                        <li><a href="{{ url('/showproduct') }}">Show Product</a></li>
                    </ul>
                </li>
                <li>
                    <a><i class="fa fa-pencil-square-o"></i> Product Reviews <span
                            class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/show-product-reviews') }}">Show Reviews</a></li>
                    </ul>
                </li>
                <!-- <li>
                    <a><i class="fa fa-pie-chart"></i> Orders <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/show-orders') }}"> Show Orders</a></li>
                    </ul>
                </li> -->

                <li>
                    <a><i class="fa fa-columns"></i>Our Testimonial<span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/add-testimonial') }}"> Add Testimonial </a></li>
                        <li><a href="{{ url('/show-testimonial') }}"> Show Testimonial </a></li>
                    </ul>
                </li>

                <li>
                    <a><i class="fa fa-columns"></i>Our Star Customers<span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/add-customer') }}"> Add Customer </a></li>
                        <li><a href="{{ url('/show-customers') }}"> Show Customers </a></li>
                    </ul>
                </li>



                <li>
                    <a>
                        <i class="fa  fa-rocket"></i> Our Insta Feeds <span class="fa fa-chevron-down"></span>
                    </a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/add-promotion') }}">add Insta Feeds</a></li>
                        <li><a href="{{ url('/show-promotions') }}">show Insta Feeds</a></li>
                    </ul>
                </li>

                <li>
                    <a>
                        <i class="fa fa-columns"></i>Our Video <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/add-video') }}"> Add Video </a></li>
                    </ul>
                </li>

                <li>
                    <a><i class="fa fa-columns"></i>Our Socials <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/add-socials') }}"> Add Socials </a></li>
                    </ul>
                </li>


                <li>
                    <a><i class="fa fa-columns"></i>Site Setting <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="{{ url('/site_setting') }}"> Site Setting </a></li>
                    </ul>
                </li>

                <li>
                    <a href="{{ route('admin.media.index') }}"><i class="fa fa-image"></i> Media Library </a>
                </li>




                {{-- <li>
                    <a><i class="fa fa-table"></i> Tables <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="tables.html">Tables</a></li>
                        <li><a href="tables_dynamic.html">Table Dynamic</a></li>
                    </ul>
                </li> --}}
                {{-- <li>
                    <a><i class="fa fa-bar-chart-o"></i> Data Presentation <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="chartjs.html">Chart JS</a></li>
                        <li><a href="chartjs2.html">Chart JS2</a></li>
                        <li><a href="morisjs.html">Moris JS</a></li>
                        <li><a href="echarts.html">ECharts</a></li>
                        <li><a href="other_charts.html">Other Charts</a></li>
                    </ul>
                </li> --}}
                {{-- <li>
                    <a><i class="fa fa-clone"></i>Layouts <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="fixed_sidebar.html">Fixed Sidebar</a></li>
                        <li><a href="fixed_footer.html">Fixed Footer</a></li>
                    </ul>
                </li> --}}
            </ul>
        </div>
        {{-- <div class="menu_section">
            <h3>Live On</h3>
            <ul class="nav side-menu">
                <li>
                    <a><i class="fa fa-bug"></i> Additional Pages <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="e_commerce.html">E-commerce</a></li>
                        <li><a href="projects.html">Projects</a></li>
                        <li><a href="project_detail.html">Project Detail</a></li>
                        <li><a href="contacts.html">Contacts</a></li>
                        <li><a href="profile.html">Profile</a></li>
                    </ul>
                </li>
                <li>
                    <a><i class="fa fa-windows"></i> Extras <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="page_403.html">403 Error</a></li>
                        <li><a href="page_404.html">404 Error</a></li>
                        <li><a href="page_500.html">500 Error</a></li>
                        <li><a href="plain_page.html">Plain Page</a></li>
                        <li><a href="login.html">Login Page</a></li>
                        <li><a href="pricing_tables.html">Pricing Tables</a></li>
                    </ul>
                </li>
                <li>
                    <a><i class="fa fa-sitemap"></i> Multilevel Menu <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                        <li><a href="#level1_1">Level One</a></li>
                        <li>
                            <a>Level One<span class="fa fa-chevron-down"></span></a>
                            <ul class="nav child_menu">
                                <li class="sub_menu"><a href="level2.html">Level Two</a></li>
                                <li><a href="#level2_1">Level Two</a></li>
                                <li><a href="#level2_2">Level Two</a></li>
                            </ul>
                        </li>
                        <li><a href="#level1_2">Level One</a></li>
                    </ul>
                </li>
                <li>
                    <a href="javascript:void(0)"><i class="fa fa-laptop"></i> Landing Page <span
                            class="label label-success pull-right">Coming Soon</span></a>
                </li>
            </ul>
        </div> --}}
    </div>
    <!-- /sidebar menu -->

    <!-- /menu footer buttons -->
    <div class="sidebar-footer hidden-small">
        <a data-toggle="tooltip" data-placement="top" href="{{ url('/changepassword') }}" title="change Password">
            <i class="fa fa-key" aria-hidden="true"></i>
        </a>
        <a data-toggle="tooltip" data-placement="top" title="Logout" href="{{ url('/logout') }}">
            <i class="fa fa-sign-out " aria-hidden="true"></i>
        </a>
    </div>
    <!-- /menu footer buttons -->
</div>