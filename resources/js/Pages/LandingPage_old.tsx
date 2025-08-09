import React from "react";
import { Button } from "@/components/ui/button";
import { useTheme } from "next-themes";
import { Link } from "@inertiajs/react";

export default function LandingPage() {
  const { theme } = useTheme();

  const isCapacitorApp = () => {
    return typeof window !== "undefined" && !!(window as any).Capacitor;
  };

  const login = (url: string) => {
    if (isCapacitorApp()) {
      // Open in the current WebView (not system browser)
      window.open(url, "_self");
    } else {
      // Regular browser login
      window.location.href = url;
    }
  };

  const handleSpotifyLogin = () => login("https://www.trackcer.com/auth/spotify");
  const handleAppleLogin = () => login("https://www.trackcer.com/login/apple");

  return (
    <div className="min-h-screen bg-[#FAFAF8] dark:bg-gray-900">
      {/* Header */}
      <header className="w-full py-6">
        <div className="max-w-[1400px] mx-auto px-4">
          <div className="bg-[#F6F6F3] dark:bg-[#0F0F0F] rounded-[15px] px-8 py-5 flex items-center justify-between">
            <Link href="/" className="flex items-center gap-3">
              <img
                src={
                  theme === "dark"
                    ? "/images/Logo-Light.svg"
                    : "/images/Logo-Dark.svg"
                }
                alt="TrackCer"
                className="h-10 w-10"
              />
              <span className="font-bold text-2xl dark:text-white">
                TrackCer
              </span>
            </Link>

            <Link href="/login">
              <Button className="bg-[#5B5FFF] hover:bg-[#4B4FEF] text-white rounded-full px-4 sm:px-8 h-10 sm:h-14 text-xs sm:text-sm font-medium">
                GET STARTED
              </Button>
            </Link>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-[1400px] mx-auto px-4 pb-16">
        <div className="relative w-full min-h-[600px] sm:min-h-[650px] md:min-h-0 md:aspect-[1231/686]">
          {/* Desktop SVG - original design */}
          <svg
            viewBox="0 0 1231 686"
            preserveAspectRatio="xMidYMid meet"
            className="hidden md:block absolute inset-0 w-full h-full"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M826.264 0H31C13.8792 0 0 13.8792 0 31V655C0 672.121 13.8792 686 31 686H1200C1217.12 686 1231 672.121 1231 655V132.301C1231 115.18 1217.12 101.301 1200 101.301H888.264C871.143 101.301 857.264 87.4213 857.264 70.3005V31C857.264 13.8792 843.385 0 826.264 0Z"
              fill={theme === "dark" ? "#0F0F0F" : "#F4F3F0"}
            />
          </svg>

          {/* Mobile SVG - same design, taller for mobile content */}
          <svg
            viewBox="0 0 800 1200"
            preserveAspectRatio="xMidYMid meet"
            className="md:hidden absolute inset-0 w-full h-full"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M536.264 0H20C8.954 0 0 8.954 0 20V1180C0 1191.046 8.954 1200 20 1200H780C791.046 1200 800 1191.046 800 1180V86C800 74.954 791.046 66 780 66H576.264C565.218 66 556.264 57.046 556.264 46V20C556.264 8.954 547.31 0 536.264 0Z"
              fill={theme === "dark" ? "#0F0F0F" : "#F4F3F0"}
            />
          </svg>

          <div className="absolute inset-0 z-10 flex items-center px-8 sm:px-12 md:px-24">
            <div className="max-w-2xl py-12 sm:py-16 md:py-0">
              <h1 className="text-5xl sm:text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-4 sm:mb-6 text-gray-900 dark:text-white">
                Transform the way
                <br />
                you experience music
              </h1>

              <p className="text-base sm:text-sm md:text-lg text-gray-600 dark:text-gray-300 mb-16 sm:mb-20 md:mb-8 max-w-xl">
                Dive beyond the artists to discover the producers crafting your
                playlist favorites. Login and unveil the studio wizards behind
                your every day music—connecting you with the hidden architects
                of the musical world.
              </p>

              <div className="flex flex-col sm:flex-row gap-3 sm:gap-4">
                <Button
                  onClick={handleAppleLogin}
                  className="bg-[#FF5842] hover:bg-[#FF4732] dark:bg-[#4033FB] dark:hover:bg-[#3529E0] text-white rounded-lg px-8 h-14"
                >
                  <div className="flex items-center gap-3">
                    <i className="fab fa-apple text-2xl" />
                    <span className="text-sm font-medium uppercase">
                      Login with Apple
                    </span>
                  </div>
                </Button>

                <Button
                  onClick={handleSpotifyLogin}
                  className="bg-black hover:bg-gray-900 text-white dark:bg-white dark:text-black dark:hover:bg-gray-100 rounded-lg px-8 h-14"
                >
                  <div className="flex items-center gap-3">
                    <i className="fab fa-spotify text-2xl" />
                    <span className="text-sm font-medium uppercase">
                      Login with Spotify
                    </span>
                  </div>
                </Button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
