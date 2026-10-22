<!-- nav.blade.php -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top d-lg-none" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
  <div class="container-fluid">
    <div class="d-flex align-items-center">
      <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <span class="navbar-brand">SBS Monitoring</span>
    </div>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        @if(Auth::check())
          <li class="nav-item">
            <a class="nav-link" href="{{ route('user.updateData') }}">
              <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('logout') }}">
              <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
          </li>
        @else
          <li class="nav-item">
            <a class="nav-link" href="{{ route('login') }}">
              <i class="bi bi-box-arrow-in-right me-1"></i> Login
            </a>
          </li>
        @endif
      </ul>
    </div>
  </div>
</nav>

<style>
  .navbar-brand {
    font-weight: 600;
    font-size: 1.1rem;
  }
  
  /* Hide navbar on desktop, show only on mobile */
  @media (min-width: 992px) {
    .navbar.fixed-top {
      display: none !important;
    }
  }
</style>