


<!DOCTYPE html>
<html lang="en">

  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Gentelella Alela! | </title>

    <!-- Bootstrap -->
    <link href="{{ asset('public/adminassets/vendors/bootstrap/dist/css/bootstrap.min.css') }}" rel="stylesheet">

    {{-- {{ asset('public/adminassets/vendors/vendors/bootstrap/dist/css/bootstrap.min.css') }} --}}
    <!-- Font Awesome -->
    <link href="{{ asset('public/adminassets/vendors/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet">
    <!-- NProgress -->
    <link href="{{ asset('public/adminassets/vendors/nprogress/nprogress.css') }}" rel="stylesheet">
    <!-- bootstrap-progressbar -->
    <link href="{{ asset('public/adminassets/vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css') }}" rel="stylesheet">
    <!-- bootstrap-daterangepicker -->
    <link href="{{ asset('public/adminassets/vendors/bootstrap-daterangepicker/daterangepicker.css') }}" rel="stylesheet">


    <link href="{{ asset('public/adminassets/vendors/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.min.css') }}" rel="stylesheet"/>

    <!-- Custom Theme Style -->
    <link href="{{ asset('public/adminassets/build/css/custom.min.css') }}" rel="stylesheet">
  </head>

  <body class="nav-md " style="background-color: #ededed!important;">

    <div class="container body">
        <div class="main_container">
            <div class="col-md-3 left_col menu_fixed">

                    <!-- sidebar menu -->
                        @include('layouts.templates.admin-sidebar')
                    <!-- /sidebar menu -->


            </div>

            <!-- top navigation -->
                @include('layouts.templates.admin-navbar')
            <!-- /top navigation -->

            <!-- page content -->
            <div class="clearfix"></div>


                @if (Session::has('Succ_msg'))

                    <div class="alert alert-success text-center font-weight-bold">
                            <?php echo Session::get('Succ_msg') ;?>
                    </div>

                @endif
            </div>
             <div class="login_wrapper change-password-wrapper mt-0" style="top: 70px">
                <div class="animate form ">
                    <section class="login_content">
                        <form id="frm" method="post" action="{{ url('/change-password').'/' }}"  >

                            @csrf

                            <h1>Change Passowrd</h1>

                            @if (Session::has('error'))

                                <div class="alert alert-danger">
                                        <?php echo Session::get('error') ;?>
                                </div>

                            @endif




                            <div>
                                <input type="password" class="form-control" placeholder="Old Password" required="" name="oldPassword" />
                            </div>

                            <div>
                                <input type="password" class="form-control" placeholder="New Password" required="" name="newPassword" />
                            </div>

                            <div>
                                <input type="password" class="form-control" placeholder="Confirm Password" required="" name="confPassword" />
                            </div>

                            <div>
                                <button class="btn btn-default submit" type="submit">Update Password</button>
                            </div>

                            <div class="clearfix"></div>


                        </form>
                    </section>
                </div>


            </div>


        </div>
        <!-- /page content -->

        <!-- footer content -->

  <div class="clearfix"></div>
            <footer >
                @include('layouts.templates.admin-footer')
            </footer>
        <!-- /footer content -->
    </div>



    <!-- jQuery -->
    <script src="{{ asset('public/adminassets/vendors/jquery/dist/jquery.min.js') }}"></script>
    <!-- Bootstrap -->
   <script src="{{ asset('public/adminassets/vendors/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <!-- FastClick -->
    <script src="{{ asset('public/adminassets/vendors/fastclick/lib/fastclick.js') }}"></script>
    <!-- NProgress -->
    <script src="{{ asset('public/adminassets/vendors/nprogress/nprogress.js') }}"></script>
    <!-- Chart.js -->
    <script src="{{ asset('public/adminassets/vendors/Chart.js/dist/Chart.min.js') }}"></script>
    <!-- jQuery Sparklines -->
    <script src="{{ asset('public/adminassets/vendors/jquery-sparkline/dist/jquery.sparkline.min.js') }}"></script>
    <!-- morris.js -->
    <script src="{{ asset('public/adminassets/vendors/raphael/raphael.min.js') }}"></script>
    <script src="{{ asset('public/adminassets/vendors/morris.js/morris.min.js') }}"></script>
    <!-- gauge.js -->
    <script src="{{ asset('public/adminassets/vendors/gauge.js/dist/gauge.min.js') }}"></script>
    <!-- bootstrap-progressbar -->
    <script src="{{ asset('public/adminassets/vendors/bootstrap-progressbar/bootstrap-progressbar.min.js') }}"></script>
    <!-- Skycons -->
    <script src="{{ asset('public/adminassets/vendors/skycons/skycons.js') }}"></script>
    <!-- Flot -->
    <script src="{{ asset('public/adminassets/vendors/Flot/jquery.flot.js') }}"></script>
    <script src="{{ asset('public/adminassets/vendors/Flot/jquery.flot.pie.js') }}"></script>
    <script src="{{ asset('public/adminassets/vendors/Flot/jquery.flot.time.js') }}"></script>
    <script src="{{ asset('public/adminassets/vendors/Flot/jquery.flot.stack.js') }}"></script>
    <script src="{{ asset('public/adminassets/vendors/Flot/jquery.flot.resize.js') }}"></script>
    <!-- Flot plugins -->
    <script src="{{ asset('public/adminassets/vendors/flot.orderbars/js/jquery.flot.orderBars.js') }}"></script>
    <script src="{{ asset('public/adminassets/vendors/flot-spline/js/jquery.flot.spline.min.js') }}"></script>
    <script src="{{ asset('public/adminassets/vendors/flot.curvedlines/curvedLines.js') }}"></script>
    <!-- DateJS -->
    <script src="{{ asset('public/adminassets/vendors/DateJS/build/date.js') }}"></script>
    <!-- bootstrap-daterangepicker -->
    <script src="{{ asset('public/adminassets/vendors/moment/min/moment.min.js') }}"></script>
    <script src="{{ asset('public/adminassets/vendors/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('public/adminassets/vendors/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js') }}"></script>
    <script src="{{ asset('public/adminassets/vendors/jquery/jquery.validate.min.js') }}" ></script>
    <script>

        $(document).ready(function() {
            $('#frm').validate({
                        rules:{
                          //   name:"required",
                            useremail:{
                                required:true,
                                email:true
                            },
                            oldPassword:{
                                required:true,
                                minlength:5
                            },
                            newPassword:{
                                required:true,
                                minlength:5
                            },
                            confPassword:{
                                required:true,
                                minlength:5
                            },
                        },messages:{
                          //   name:"Please enter your name",
                            useremail:{
                                required:"Please enter email",
                                email:"Please enter valid email",
                            },
                            oldPassword:{
                                required:"Please enter your old password",
                                minlength:"Password must be 5 char long"
                            },
                            newPassword:{
                                required:"Please enter your new password",
                                minlength:"Password must be 5 char long"
                            },
                           confPassword:{
                                required:"Please enter your new password",
                                minlength:"Password must be 5 char long"
                            },
                        },
                        submitHandler:function(form){
                            form.submit();
                        }
                    });
        });

    </script>
    <script src="{{ asset('public/adminassets/build/js/custom.min.js') }}"></script>

  </body>
</html>

