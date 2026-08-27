<!DOCTYPE html>
<html lang="en">

<head>


    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <title>Corrugation Boxes </title>

    <!-- Bootstrap -->
    <link href="{{ asset('adminassets/vendors/bootstrap/dist/css/bootstrap.min.css') }}" rel="stylesheet">




    {{-- {{ asset('public/adminassets/vendors/vendors/bootstrap/dist/css/bootstrap.min.css') }} --}}
    <!-- Font Awesome -->
    <link href="{{ asset('adminassets/vendors/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet">
    <!-- NProgress -->
    <link href="{{ asset('adminassets/vendors/nprogress/nprogress.css') }}" rel="stylesheet">
    <!-- bootstrap-progressbar -->
    <link href="{{ asset('adminassets/vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css') }}"
        rel="stylesheet">
    <!-- bootstrap-daterangepicker -->
    <link href="{{ asset('adminassets/vendors/bootstrap-daterangepicker/daterangepicker.css') }}" rel="stylesheet">

    <link href="{{ asset('adminassets/build/css/select2.min.css') }}" rel="stylesheet" />

    <link href="{{ asset('adminassets/vendors/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.min.css') }}"
        rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('adminassets/build/css/sweetalert.min.css') }}" />

    <link href="{{ asset('adminassets/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css') }}" rel="stylesheet">
    <!-- Custom Theme Style -->
    <link href="{{ asset('adminassets/build/css/custom.min.css') }}" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}" />
</head>

<body class="nav-md">

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
            <main>
                @yield('content')
            </main>
        </div>
        <!-- /page content -->

        <!-- footer content -->


        <footer>
            @include('layouts.templates.admin-footer')
        </footer>
        <!-- /footer content -->
    </div>



    <!-- jQuery -->
    <script src="{{ asset('adminassets/vendors/jquery/dist/jquery.min.js') }}"></script>
    <!-- Bootstrap -->
    <script src="{{ asset('adminassets/vendors/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <!-- FastClick -->
    <script src="{{ asset('adminassets/vendors/fastclick/lib/fastclick.js') }}"></script>
    <!-- NProgress -->
    <script src="{{ asset('adminassets/vendors/nprogress/nprogress.js') }}"></script>
    <!-- Chart.js -->
    <script src="{{ asset('adminassets/vendors/Chart.js/dist/Chart.min.js') }}"></script>
    <!-- jQuery Sparklines -->
    <script src="{{ asset('adminassets/vendors/jquery-sparkline/dist/jquery.sparkline.min.js') }}"></script>
    <!-- morris.js -->
    <script src="{{ asset('adminassets/vendors/raphael/raphael.min.js') }}"></script>
    <script src="{{ asset('adminassets/vendors/morris.js/morris.min.js') }}"></script>
    <!-- gauge.js -->
    <script src="{{ asset('adminassets/vendors/gauge.js/dist/gauge.min.js') }}"></script>
    <!-- bootstrap-progressbar -->
    <script src="{{ asset('adminassets/vendors/bootstrap-progressbar/bootstrap-progressbar.min.js') }}"></script>
    <!-- Skycons -->
    <script src="{{ asset('adminassets/vendors/skycons/skycons.js') }}"></script>
    <!-- Flot -->
    <script src="{{ asset('adminassets/vendors/Flot/jquery.flot.js') }}"></script>
    <script src="{{ asset('adminassets/vendors/Flot/jquery.flot.pie.js') }}"></script>
    <script src="{{ asset('adminassets/vendors/Flot/jquery.flot.time.js') }}"></script>
    <script src="{{ asset('adminassets/vendors/Flot/jquery.flot.stack.js') }}"></script>
    <script src="{{ asset('adminassets/vendors/Flot/jquery.flot.resize.js') }}"></script>

    <!-- ckeditor plugins -->





    <script src="{{ asset('ckeditor/ckeditor.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof CKEDITOR === 'undefined') {
                return;
            }

            ['editor1', 'editor11', 'editor2'].forEach(function (editorId) {
                if (document.getElementById(editorId) && !CKEDITOR.instances[editorId]) {
                    CKEDITOR.replace(editorId, {
                        filebrowserUploadUrl: "{{ asset('ckeditor/ck_upload.php') }}",
                        filebrowserUploadMethod: 'form'
                    });
                }
            });
        });
    </script>
    <!-- Flot plugins -->
    <script src="{{ asset('adminassets/vendors/flot.orderbars/js/jquery.flot.orderBars.js') }}"></script>
    <script src="{{ asset('adminassets/vendors/flot-spline/js/jquery.flot.spline.min.js') }}"></script>
    <script src="{{ asset('adminassets/vendors/flot.curvedlines/curvedLines.js') }}"></script>
    <!-- DateJS -->
    <script src="{{ asset('adminassets/vendors/DateJS/build/date.js') }}"></script>

    <script src="{{ asset('adminassets/vendors/jquery/jquery.validate.min.js') }}"></script>

    <!-- select2 plugins -->

    <script src="{{ asset('adminassets/build/js/select2.min.js') }}"></script>
    <script>

        $("#multipleSelect").select2({
            placeholder: "Select Related Product",
            allowClear: true
        });

    </script>



    <script>

        $("#multipleSelectState").select2({
            placeholder: "Select State",
            allowClear: true
        });

    </script>

    <script>

        $("#multipleSelectState_").select2({
            placeholder: "Select State",
            allowClear: true
        });

    </script>

    <!-- bootstrap-daterangepicker -->
    <script src="{{ asset('adminassets/vendors/moment/min/moment.min.js') }}"></script>
    <script src="{{ asset('adminassets/vendors/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
    <script
        src="{{ asset('adminassets/vendors/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js') }}"></script>

    <script src="{{ asset('adminassets/vendors/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminassets/vendors/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script src="{{ asset('adminassets/vendors/sweetalert/bootstrap-sweetalert.min.js') }}"></script>

    <script src="{{ asset('adminassets/build/js/custom.min.js') }}"></script>


    @yield('scripts')


</body>

</html>
