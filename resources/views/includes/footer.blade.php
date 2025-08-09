@if(auth()->check())
    <footer class="footer-dark">
        <div class="container py-4">
            <div class="row">
                <!-- Branding Section -->
                <div class="col-md-4">
                    <h5>TRACKCER</h5>
                    <p>Connecting music producers, tracks, and fans through data-driven insights.</p>
                    <p>&copy; 2025 TRACKCER. All Rights Reserved.</p>
                </div>

                <!-- Quick Links Section -->
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('playlists.index') }}">Playlists</a></li>
                        <li><a href="{{ route('tracks.index') }}">Tracks</a></li>
                        <li><a href="{{ url('/producer') }}">Producers</a></li>
                    </ul>
                </div>

                <!-- User and Contact Info -->
                <div class="col-md-4">
                    @if(auth()->check())
                        <h5>Logged in as:</h5>
                        <p>{{ auth()->user()->name }}</p>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">Logout</button>
                        </form>
                    @else
                        <h5>Welcome to Producer App</h5>
                        <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">Login</a>
                    @endif

                    <h5 class="mt-3">Contact Us</h5>
                    <p>Email: support@trackcer.com</p>
                    <p>Phone: +123 456 7890</p>
                </div>
            </div>
        </div>
    </footer>
@endif


<style>
    /* Make sure these styles are added to your stylesheet or in this component */
    html, body {
        height: 100%;
        margin: 0;
    }

    #app {
        display: flex;
        flex-direction: column;
        min-height: 100vh; /* Full viewport height */
    }

    main {
        flex: 1 0 auto; /* Makes the main content expand to push footer down */
    }

    /* Your existing footer styles with the added flex-shrink property */
    .footer-dark {
        flex-shrink: 0; /* Prevents the footer from shrinking */
        background-color: #2c2f33; /* Dark background */
        color: #dcdcdc; /* Light text */
        padding: 20px 0;
    }

    .footer-dark h5 {
        font-size: 18px;
        color: #ffffff;
        margin-bottom: 15px;
        text-transform: uppercase;
        font-weight: bold;
    }

    .footer-dark p {
        font-size: 14px;
        color: #d1d1d1;
        margin-bottom: 10px;
    }

    .footer-links {
        list-style: none;
        padding: 0;
    }

    .footer-links li {
        margin-bottom: 8px;
    }

    .footer-links a {
        text-decoration: none;
        color:  #0e7490;
        transition: color 0.2s ease-in-out;
    }

    .footer-links a:hover {
        color:#06b6d4; /* Highlight color */
    }

    .footer-dark a.btn {
        margin-top: 5px;
    }

    @media (max-width: 768px) {
        .footer-dark {
            text-align: center;
        }

        .footer-dark .row > div {
            margin-bottom: 20px;
        }
    }
</style>
