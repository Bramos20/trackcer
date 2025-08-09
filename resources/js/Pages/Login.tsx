import React from "react"
import { Head, Link } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardFooter } from "@/components/ui/card"
import { useTheme } from "next-themes";
import blurBg from "@/assets/blur_bg.png";

export default function Login({ error }) {
  const { theme } = useTheme();
  return (
    <>
      <Head title="Login" />
      <div className="min-h-screen bg-gray-50 dark:bg-background flex items-center justify-center p-4 relative">
        <div className="w-full max-w-md">
          <Card className="shadow-lg border-0">
            <CardHeader className="space-y-1 pb-6">
              <h2 className="text-2xl md:text-3xl font-bold text-center">
                Welcome Back
              </h2>
              <p className="text-sm text-gray-600 dark:text-gray-400 text-center">
                Choose your preferred streaming service
              </p>
            </CardHeader>
            
            <CardContent className="space-y-4 px-6">
              {error && (
                <div className="p-4 bg-red-50 border-l-4 border-red-400 rounded-r-md">
                  <p className="text-sm text-red-700 font-medium">{error}</p>
                </div>
              )}
              
              <div className="space-y-3">
                <Button
                  asChild
                  className="w-full h-12 text-base font-medium bg-[#1db954] hover:bg-[#1ed760] text-white border-0 transition-colors duration-200"
                >
                  <a href={route("auth.spotify")} className="flex items-center justify-center gap-3">
                    <i className="fab fa-spotify text-xl" />
                    <span>Continue with Spotify</span>
                  </a>
                </Button>

                <Button
                  asChild
                  className="w-full h-12 text-base font-medium bg-black hover:bg-gray-800 text-white border-0 transition-colors duration-200"
                >
                  <a href={route("login.apple")} className="flex items-center justify-center gap-3">
                    <i className="fab fa-apple text-xl" />
                    <span>Sign in with Apple</span>
                  </a>
                </Button>
              </div>
            </CardContent>
            
            <CardFooter className="pt-6 pb-6">
              <div className="w-full text-center">
                <p className="text-xs text-gray-500">
                  By continuing, you agree to our{" "}
                  <Link href="/legal" className="underline hover:text-gray-700 transition-colors">
                    Terms of Service
                  </Link>
                </p>
              </div>
            </CardFooter>
          </Card>
          
          {/* Optional: Add some branding or additional info */}
          <div className="mt-8 text-center">
            <p className="text-xs text-gray-400">
              Secure authentication powered by your music streaming service
            </p>
          </div>
        </div>
      </div>
            <div 
              className="w-full h-full -z-10 bg-no-repeat bg-cover absolute bottom-0 left-1/2 -translate-x-1/2"
              style={{
                backgroundColor: theme === 'dark' ? 'transparent' : '#FAFAF8',
                backgroundImage: theme === 'dark' ? `url(${blurBg})` : 'none',
              }}
            ></div>
    </>
  )
}