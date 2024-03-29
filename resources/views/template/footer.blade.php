<!-- Footer -->
<footer class="page-footer font-small bg-success text-white">

  <!-- Footer Links -->
  <div class="container text-center text-md-left">

    <!-- Grid row -->
    <div class="row">

      <!-- Grid column -->
      <div class="col-md-3 mx-auto">

        <!-- Links -->
        <h5 class="font-weight-bold text-capitalize mt-3 mb-4">Contact Us</h5>
        <hr class="accent-3 mb-4 mt-0 d-inline-block mx-auto bg-warning" style="width: 60px;">
        <p>
          <i class="fas fa-home mr-3"></i><small>{{ env('CONTACT_LOCATION') }}</small></p>
        <p>
          <i class="fas fa-envelope mr-3"></i><small>{{ env('CONTACT_EMAIL') }}</small></p>
        <p>
          <i class="fas fa-phone mr-3"></i><small>{{ env('CONTACT_PHONE') }}</small></p>
      </div>
      <!-- Grid column -->

      <hr class="clearfix w-100 d-md-none">

      <!-- Grid column -->
      <div class="col-md-3 mx-auto">

        <!-- Links -->
        <h5 class="font-weight-bold text-capitalize mt-3 mb-4">Quick Links</h5>
        <hr class="accent-3 mb-4 mt-0 d-inline-block mx-auto bg-warning" style="width: 60px;">

        <p>
          <a class="text-white" href="/account"><small>Your Account</small></a>
        </p>
        <p>
          <a class="text-white" href="/support"><small>FAQs</small></a>
        </p>
        <p>
          <a class="text-white" href="/support"><small>HowTo</small></a>
        </p>

      </div>
      <!-- Grid column -->

      <hr class="clearfix w-100 d-md-none">

      <!-- Grid column -->
      <div class="col-md-3 mx-auto">

        <!-- Links -->
        <h5 class="font-weight-bold text-capitalize mt-3 mb-4">Latest Tweets</h5>
        <hr class="accent-3 mb-4 mt-0 d-inline-block mx-auto bg-warning" style="width: 60px;">

        <a class="twitter-timeline" data-height="200" href="https://twitter.com/{{ env('HANDLE_TWITTER') }}?ref_src=twsrc%5Etfw"></a>
        <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>

      </div>
      <!-- Grid column -->

      <hr class="clearfix w-100 d-md-none">

      <!-- Grid column -->
      <div class="col-md-3 mx-auto">

        <!-- Links -->
        <h5 class="font-weight-bold text-capitalize mt-3 mb-4">Connect with Us</h5>
        <hr class="accent-3 mb-4 mt-0 d-inline-block mx-auto bg-warning" style="width: 60px;">

        <p class="text-center">
          <!-- Facebook -->
          <a type="button" class="btn-floating btn-fb" target="_blank" href="https://www.facebook.com/{{ env('HANDLE_FACEBOOK') }}/">
            <small><i class="fab fa-facebook-f"></i>acebook</small>
          </a>
        </p>
        <p class="text-center">
          <!-- Twitter -->
          <a type="button" class="btn-floating btn-tw" target="_blank" href="https://twitter.com/{{ env('HANDLE_TWITTER') }}">
            <small><i class="fab fa-twitter"></i>Twitter</small>
          </a>
        </p>
        <p class="text-center">
          <!-- Instagram -->
          <a type="button" class="btn-floating btn-insta" target="_blank" href="https://www.instagram.com/{{ env('HANDLE_INSTAGRAM') }}">
            <small><i class="fab fa-instagram"></i>Instagram</small>
          </a>
        </p>

      </div>
      <!-- Grid column -->

    </div>
    <!-- Grid row -->

  </div>
  <!-- Footer Links -->

  <!-- Copyright -->
  <div class="footer-copyright text-center py-3 bg-primary">
    <div>Copyright &copy; <a href="{{ 'http://www.'.config('app.domain') }}" target="_blank" class="text-white">{{ config('app.label') }}</a> {{date('Y')}}</div>
  </div>
  <!-- Copyright -->

</footer>
<!-- Footer -->
