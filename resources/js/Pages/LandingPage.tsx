import React from "react";
import logo from "@/assets/logo.svg";
import BGImage from "@/assets/bg-image.png";
import BGImageDark from "@/assets/bg-image-dark.png";
import Logo1 from "@/assets/logo-1.png";
import Logo2 from "@/assets/logo-2.png";
import Logo3 from "@/assets/logo-3.png";
import Logo4 from "@/assets/logo-4.png";
import Logo5 from "@/assets/logo-5.png";
import blurBg from "@/assets/blur_bg.png";

import { Button } from "@/components/ui/button";
import { useTheme } from "next-themes";
import { Link } from "@inertiajs/react";
import { useIsMobile } from "@/hooks/use-mobile";
import topRight from "@/assets/top-right.png";

export default function LandingPage() {
  const { theme } = useTheme();
  const smallPhone = useIsMobile(620);

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

  const handleSpotifyLogin = () => login("https://www.trackcer.app/auth/spotify");
  const handleAppleLogin = () => login("https://www.trackcer.app/login/apple");

  return (
    <div className="min-h-screen relative font-inter px-4 lg:px-10">
      {/* Header */}
      <header className="w-full py-6">
        <div className="max-w-[1400px] mx-auto">
          <div className="bg-[#F6F6F3] dark:bg-[#0F0F0F] rounded-[15px] px-8 py-5 flex items-center justify-between">
            <Link href="/" className="flex items-center gap-3">
              <img
                src={logo}
                alt="TrackCer"
                className="h-[37px] w-[148px] object-contain dark:brightness-[1000] dark:invert"
              />
            </Link>

            <Link href="/login">
              <Button
                size={smallPhone ? "lg" : "default"}
                className="rounded-full"
                >GET STARTED
                </Button>
            </Link>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="relative max-w-[1400px] overflow-hidden mx-auto min-h-[calc(100vh-127px)]">
        {/* Background radius blur image  */}
        <div className="rounded-xl overflow-hidden">
          <div
            className="relative w-full z-10 p-6 pt-12 sm:p-12 rounded-3xl topRightMain bg-[#F6F5F2] dark:bg-[#110E0F]"
            style={{
              borderTopRightRadius: "0rem",
              backdropFilter: "blur(40px)",
              WebkitMask: `url(${topRight}) center / contain no-repeat, linear-gradient(#000000 0 0)`,
              maskSize: "5rem 4rem",
              maskPosition: "top right",
              maskComposite: "exclude",
            }}
          >
            {/* Content */}
            <div className="md:mb-48">
              <div className="max-w-2xl mt-10">
                <Button
                  variant={theme === "dark" ? "default" : "secondary"}
                  className="flex items-center whitespace-normal h-12 sm:h-8 gap-3 px-5.5 mb-4 sm:mb-6"
                  size={"sm"}
                >
                  <i className="fa-solid fa-circle"></i>
                  <span className="text-sm font-medium uppercase">HAS BEGUN. LEARN MORE!</span>
                </Button>
                <h1 className="text-3xl sm:text-4xl md:text-5xl lg:text-[57px] leading-tight mb-4 sm:mb-6 text-gray-900 dark:text-white font-degular md:max-w-xl">
                  Transform the way <br className="sm:block hidden" />
                  you experience music
                </h1>

                <p className="text-sm text-justify sm:text-base md:text-lg text-gray-600 dark:text-gray-300 mb-6 sm:mb-8 md:max-w-[550px]">Dive beyond the artists to discover the producers crafting your playlist favorites. Login and unveil the studio wizards behind your every day music—connecting you with the hidden architects of the musical world.</p>

                {/* CTA Buttons */}
                <div className="flex flex-col sm:flex-row gap-3 sm:gap-4">
                  <Button
                    onClick={handleAppleLogin}
                    variant={theme === "dark" ? "default" : "secondary"}
                    className="flex items-center gap-3"
                  >
                      <i className="fab fa-apple text-2xl" />
                      <span className="text-sm font-medium uppercase">Login with Apple</span>
                  </Button>
                  <Button
                    onClick={handleSpotifyLogin}
                    variant={"dark"}
                    className="flex items-center gap-3"
                  >
                      <i className="fab fa-spotify text-2xl" />
                      <span className="text-sm font-medium uppercase">Login with Spotify</span>
                  </Button>
                </div>
              </div>
            </div>
            {/* bg image */}
            <div className="md:block ">
              <img
                src={theme === "dark" ? BGImageDark : BGImage}
                className="absolute bottom-0 right-0 -z-5"
              />
            </div>
          </div>
        </div>
        {/* Logos Section */}
        <div className="w-full mx-auto flex flex-wrap justify-center items-center gap-12 py-14 md:py-8 relative z-10">
          <img
            src={Logo1}
            className="w-20"
          />
          <img
            src={Logo2}
            className="w-20"
          />
          <img
            src={Logo3}
            className="w-20"
          />
          <img
            src={Logo4}
            className="w-20"
          />
          <img
            src={Logo5}
            className="w-20"
          />
        </div>
      </main>

      <div
        className="w-full h-full -z-10 bg-no-repeat bg-cover absolute bottom-0 left-1/2 -translate-x-1/2"
        style={{
          backgroundColor: theme === 'dark' ? 'transparent' : '#FAFAF8',
          backgroundImage: theme === 'dark' ? `url(${blurBg})` : 'none',
        }}
      ></div>
    </div>
  );
}
