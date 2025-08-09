import React, { type ReactNode } from 'react'
import { Link, usePage } from '@inertiajs/react'
import { Toaster } from 'sonner'
import DataUpdateNotifier from '@/components/DataUpdateNotifier'
import SearchModal from '@/components/SearchModal'
import { Search, Bell, BellRing, LayoutDashboard, Music, Users, Mic2, Settings, HelpCircle, Gavel, LogOut, Moon, Sun, PanelLeftClose, PanelLeft, PanelRight, Menu, X } from 'lucide-react'
// import blurBg from "@/assets/blur_bg.png";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu"
import { useTheme } from "next-themes"

interface IntegratedLayoutProps {
  children: ReactNode
  user: {
    name: string
    email: string
    avatar?: string
  }
  enableDataUpdateNotifier?: boolean
}

export default function IntegratedLayout({ children, user, enableDataUpdateNotifier = false }: IntegratedLayoutProps) {
  const { url, props } = usePage<any>()
  const unreadCount = props.unreadCount || 0
  const { theme, setTheme } = useTheme()

  // Get current page info for breadcrumb
  const getCurrentPageInfo = () => {
    const path = url.split('?')[0] // Remove query params
    const segments = path.split('/').filter(Boolean)

    // Dashboard page
    if (segments.length === 0 || segments[0] === 'dashboard') {
      return [
        { name: 'Dashboard', href: '/dashboard' },
        { name: 'Music Analytics', href: null }
      ]
    }

    // Map routes to display names
    const routeMap: Record<string, string> = {
      'tracks': 'Tracks',
      'producers': 'Producers',
      'producer': 'Producers', // Map singular to plural
      'artists': 'Artists',
      'artist': 'Artists', // Map singular to plural
      'settings': 'Settings',
      'notifications': 'Notifications',
      'support': 'Support',
      'legal': 'Legal'
    }

    const breadcrumbs = [{ name: 'Dashboard', href: '/dashboard' }]

    // Add main section
    const sectionName = routeMap[segments[0]] || segments[0].charAt(0).toUpperCase() + segments[0].slice(1)
    // For producer/artist detail pages, link to the list page
    const sectionHref = segments[0] === 'producer' ? '/producers' :
                       segments[0] === 'artist' ? '/artists' :
                       `/${segments[0]}`
    breadcrumbs.push({ name: sectionName, href: sectionHref })

    // Handle nested routes (e.g., producer/artist details)
    if (segments.length > 1) {
      // For producers and artists, get the actual name from props if available
      let detailName = segments[1]

      if (segments[0] === 'producer' || segments[0] === 'producers') {
        // Try to get producer name from props
        const producerName = props?.producer?.name
        if (producerName) {
          detailName = producerName
        } else {
          detailName = 'Producer Details'
        }
      } else if (segments[0] === 'artist' || segments[0] === 'artists') {
        // Try to get artist name from props
        const artistName = props?.artist?.name
        if (artistName) {
          detailName = artistName
        } else {
          detailName = 'Artist Details'
        }
      }

      breadcrumbs.push({ name: detailName, href: null })
    }

    return breadcrumbs
  }

  const breadcrumbs = getCurrentPageInfo()
  // Get initial sidebar state from localStorage
  const getInitialSidebarState = () => {
    if (typeof window !== 'undefined') {
      const saved = localStorage.getItem('sidebar-collapsed')
      return saved ? saved === 'false' : false // Default to collapsed
    }
    return false
  }

  const [sidebarOpen, setSidebarOpen] = React.useState(false) // Always start collapsed
  const [isDesktop, setIsDesktop] = React.useState(false)
  const [isSearchOpen, setIsSearchOpen] = React.useState(false)

  // Load saved state after mount to avoid hydration mismatch
  React.useEffect(() => {
    const saved = localStorage.getItem('sidebar-collapsed')
    if (saved !== null) {
      setSidebarOpen(saved === 'true')
    }

    // Check if desktop
    const checkDesktop = () => {
      setIsDesktop(window.innerWidth >= 1024)
    }
    checkDesktop()
    window.addEventListener('resize', checkDesktop)
    return () => window.removeEventListener('resize', checkDesktop)
  }, [])
  const [mobileSidebarOpen, setMobileSidebarOpen] = React.useState(false)

  const navigation = [
    {
      label: "Dashboard",
      icon: LayoutDashboard,
      href: "/dashboard",
    },
    {
      label: "Tracks",
      icon: Music,
      href: "/tracks",
    },
    {
      label: "Producers",
      icon: Users,
      href: "/producers",
    },
    {
      label: "Artists",
      icon: Mic2,
      href: "/artists",
    },
  ]

  return (
    <div className="min-h-screen bg-background flex relative overflow-x-hidden">
      {/* Sidebar Overlay - Shows on desktop when sidebar is open and on mobile when sidebar is open */}
      {(mobileSidebarOpen || (sidebarOpen && isDesktop)) && (
        <div
          className={cn(
            "fixed inset-0 bg-black/20 backdrop-blur-sm z-40",
            mobileSidebarOpen ? "block" : "hidden lg:block"
          )}
          onClick={() => {
            if (mobileSidebarOpen) {
              setMobileSidebarOpen(false)
            } else {
              setSidebarOpen(false)
              localStorage.setItem('sidebar-collapsed', 'false')
            }
          }}
        />
      )}

      {/* Sidebar - Full Height */}
      <aside className={cn(
        "flex-shrink-0 p-4 transition-all duration-300",
        "fixed inset-y-0 left-0 z-50", // Always fixed positioning
        mobileSidebarOpen ? "translate-x-0" : "-translate-x-full lg:translate-x-0",
        // Mobile always shows expanded, desktop respects sidebarOpen state
        mobileSidebarOpen ? "w-[280px]" : (sidebarOpen ? "w-[280px]" : "w-[120px]"),
        "lg:block",
        "max-w-[85vw] sm:max-w-none", // Prevent sidebar from being too wide on mobile
        // Add top padding on mobile to account for header
        mobileSidebarOpen && "pt-20"
      )}>
        <div 
          className={cn(
            "rounded-[27px] h-full flex flex-col relative",
            // Different background for mobile to ensure readability
            mobileSidebarOpen ? "bg-[#F2F2F2]/[0.95] dark:bg-black/[0.95]" : sidebarOpen ? "bg-[#F2F2F2]/[0.36] dark:bg-black/[0.19]" : "bg-[#F2F2F2]/[0.36] dark:bg-black/[0.19]",
            // Mobile always shows expanded padding, desktop respects sidebarOpen state
            mobileSidebarOpen ? "px-8 py-10" : sidebarOpen ? "px-8 py-10" : "px-4 py-10"
          )}
        >
          {/* Header with Logo */}
          <div className="mb-13">
            <Link href="/dashboard" className={cn(
              "flex items-center gap-3",
              !sidebarOpen && !mobileSidebarOpen && "justify-center"
            )}>
              <img
                src={theme === 'dark' ? '/images/Logo-Light.svg' : '/images/Logo-Dark.svg'}
                alt="TrackCer"
                className="w-10 h-10 flex-shrink-0"
              />
              {(sidebarOpen || mobileSidebarOpen) && (
                <span className="font-bold text-2xl text-gray-900 dark:text-white">TrackCer</span>
              )}
            </Link>
          </div>

          <nav className="flex-1 flex flex-col">
            {/* Toggle Button at top - Hidden on mobile */}
            <div className={cn(
              "hidden lg:flex justify-center mb-42",
              sidebarOpen && "justify-start"
            )}>
              <Button
                variant="ghost"
                onClick={() => {
                  const newState = !sidebarOpen
                  setSidebarOpen(newState)
                  localStorage.setItem('sidebar-collapsed', String(newState))
                }}
                className="p-0 h-auto hover:bg-transparent group rounded-full"
                title={sidebarOpen ? "Collapse Sidebar" : "Expand Sidebar"}
              >
                <div className={cn(
                  "flex items-center justify-center rounded-full transition-all duration-200",
                  "w-12 h-12",
                  "bg-white dark:bg-white/[0.13] text-gray-700 dark:text-white group-hover:bg-white/90 dark:group-hover:bg-white/[0.20]"
                )}>
                  {sidebarOpen ? (
                    <PanelRight className="h-5 w-5" />
                  ) : (
                    <PanelLeft className="h-5 w-5" />
                  )}
                </div>
              </Button>
            </div>

            {/* Navigation items */}
            <div className="space-y-3">
              {navigation.map((item) => {
                const isActive = url.startsWith(item.href)
                const Icon = item.icon

                return (
                  <Link
                    key={item.href}
                    href={item.href}
                    onClick={() => {
                      setMobileSidebarOpen(false)
                      // Collapse sidebar when clicking a navigation item (only on desktop when expanded)
                      if (sidebarOpen && isDesktop) {
                        setSidebarOpen(false)
                        localStorage.setItem('sidebar-collapsed', 'false')
                      }
                    }}
                    className={cn(
                      "flex items-center gap-4 transition-all duration-200 group",
                      (sidebarOpen || mobileSidebarOpen) ? "rounded-full" : "justify-center"
                    )}
                    title={!sidebarOpen && !mobileSidebarOpen ? item.label : undefined}
                  >
                    {(sidebarOpen || mobileSidebarOpen) ? (
                      // Expanded sidebar
                      isActive ? (
                        // Active state - show full button with background
                        <div className="flex items-center gap-4 rounded-full transition-all duration-200 w-full bg-[#775AFF] text-white">
                          <div className="flex items-center justify-center rounded-full w-12 h-12 bg-[#6A4BFB]">
                            <Icon className="h-5 w-5" />
                          </div>
                          <span className="font-medium text-lg pr-6">{item.label}</span>
                        </div>
                      ) : (
                        // Inactive state - show icon with background and text outside
                        <>
                          <div className="flex items-center justify-center rounded-full transition-all duration-200 w-12 h-12 bg-white dark:bg-white/[0.13] text-gray-700 dark:text-white group-hover:bg-white/90 dark:group-hover:bg-white/[0.20]">
                            <Icon className="h-5 w-5" />
                          </div>
                          <span className="font-medium text-gray-700 dark:text-gray-400">
                            {item.label}
                          </span>
                        </>
                      )
                    ) : (
                      // Collapsed sidebar - show only icon
                      <div className={cn(
                        "flex items-center justify-center rounded-full transition-all duration-200",
                        "w-12 h-12",
                        isActive
                          ? "bg-[#6A4BFB] text-white"
                          : "bg-white dark:bg-white/[0.13] text-gray-700 dark:text-white group-hover:bg-white/90 dark:group-hover:bg-white/[0.20]"
                      )}>
                        <Icon className="h-5 w-5" />
                      </div>
                    )}
                  </Link>
                )
              })}
            </div>
          </nav>

        </div>
      </aside>

      {/* Main Content */}
      <div className={cn(
        "flex-1 transition-all duration-300 min-w-0 overflow-x-hidden",
        "lg:ml-[120px]" // Always leave space for collapsed sidebar on desktop
      )}>
        {/* Mobile Header */}
        <header className="lg:hidden fixed top-0 left-0 right-0 z-[60] px-4 py-3 mobile-header backdrop-blur-md border-0 shadow-none outline-none" style={{ borderBottom: 'none' }}>
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Link href="/dashboard" className="flex items-center gap-8">
                <img
                  src={theme === 'dark' ? '/images/Logo-Light.svg' : '/images/Logo-Dark.svg'}
                  alt="TrackCer"
                  className="w-8 h-8"
                />
              </Link>
              <Button
                variant="ghost"
                size="icon"
                onClick={() => setMobileSidebarOpen(!mobileSidebarOpen)}
                className="lg:hidden bg-[#FFFFFF] dark:bg-[#1A1A1A] text-gray-600 dark:text-gray-400 h-10 w-10 rounded-full hover:bg-gray-100 dark:hover:bg-[#252525]"
              >
                <PanelRight className="h-4 w-4" />
              </Button>
            </div>

            <div className="flex items-center gap-2">
              {/* Search button */}
              <Button
                variant="ghost"
                size="icon"
                className="bg-[#FFFFFF] dark:bg-[#1A1A1A] text-gray-600 dark:text-gray-400 h-10 w-10 rounded-full hover:bg-gray-100 dark:hover:bg-[#252525]"
                onClick={() => setIsSearchOpen(true)}
              >
                <Search className="h-4 w-4 text-gray-700 dark:text-gray-400" />
              </Button>

              {/* Notifications */}
              <Link href="/notifications">
                <Button variant="ghost" size="icon" className="bg-[#FFFFFF] dark:bg-[#1A1A1A] text-gray-600 dark:text-gray-400 h-10 w-10 rounded-full hover:bg-gray-100 dark:hover:bg-[#252525]">
                  {unreadCount > 0 ? (
                    <BellRing className="h-4 w-4" style={{ color: '#DF1C41' }} />
                  ) : (
                    <Bell className="h-4 w-4 text-gray-700 dark:text-gray-400" />
                  )}
                </Button>
              </Link>

              {/* User dropdown */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <button className="bg-[#FFFFFF] dark:bg-[#1A1A1A] hover:bg-gray-100 dark:hover:bg-[#252525] rounded-full pl-1.5 pr-3 h-10 flex items-center gap-2 cursor-pointer transition-colors">
                    <Avatar className="h-7 w-7">
                      <AvatarImage src={user.avatar} alt={user.name} />
                      <AvatarFallback className="bg-purple-500 text-white text-xs">
                        {user.name?.[0]?.toUpperCase() || "U"}
                      </AvatarFallback>
                    </Avatar>
                    <span className="text-sm font-medium text-gray-900 dark:text-white">
                      {user.name?.split(' ')[0] || 'User'}
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-600 dark:text-gray-400">
                      <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56 z-[9999] bg-white/[0.35] dark:bg-[#18191E]/[0.39] backdrop-blur-md rounded-2xl border-0 p-2" sideOffset={8}>
                  <DropdownMenuItem
                    onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
                    className="cursor-pointer flex items-center rounded-sm mb-1"
                  >
                    {theme === 'dark' ? <Sun className="mr-2 h-4 w-4" /> : <Moon className="mr-2 h-4 w-4" />}
                    <span>{theme === 'dark' ? 'Light mode' : 'Dark mode'}</span>
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem asChild>
                    <Link href="/settings" className="cursor-pointer flex items-center w-full rounded-sm mb-1">
                      <Settings className="mr-2 h-4 w-4 flex-shrink-0" />
                      <span>Settings</span>
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem asChild>
                    <Link href="/support" className="cursor-pointer flex items-center w-full rounded-sm mb-1">
                      <HelpCircle className="mr-2 h-4 w-4 flex-shrink-0" />
                      <span>Support</span>
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem asChild>
                    <Link href="/legal" className="cursor-pointer flex items-center w-full rounded-sm mb-1">
                      <Gavel className="mr-2 h-4 w-4 flex-shrink-0" />
                      <span>Legal</span>
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem asChild>
                    <Link
                      href="/logout"
                      method="post"
                      as="button"
                      className="w-full cursor-pointer text-red-600 focus:text-red-600 flex items-center rounded-sm"
                    >
                      <LogOut className="mr-2 h-4 w-4" />
                      <span>Log out</span>
                    </Link>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>
        </header>

        <div className={cn(
          "mobile-content transition-all duration-300",
          "lg:flex-1 lg:flex lg:flex-col"
        )}>
          {/* Desktop Header Container */}
          <div className={cn("hidden lg:block pt-12 mb-7 px-4 lg:px-9")}>
            <header className={cn("flex items-center justify-between h-14")}>
              {/* Left side - Breadcrumb */}
              <div className="bg-[#FFFFFF] dark:bg-[#191919] rounded-full inline-flex items-center gap-3 text-base pr-6">
                {breadcrumbs.map((crumb, index) => (
                  <React.Fragment key={index}>
                    {index > 0 && (
                      <span className="text-gray-500 dark:text-gray-400">&gt;</span>
                    )}
                    {index === 0 ? (
                      <Link
                        href={crumb.href}
                        className="bg-[#F6F6F6] dark:bg-[#202020] text-gray-900 dark:text-white font-medium rounded-full px-6 py-3"
                      >
                        {crumb.name}
                      </Link>
                    ) : crumb.href ? (
                      <Link
                        href={crumb.href}
                        className="text-gray-900 dark:text-white font-medium hover:text-gray-600 dark:hover:text-gray-300"
                      >
                        {crumb.name}
                      </Link>
                    ) : (
                      <span className="text-gray-900 dark:text-white font-medium">{crumb.name}</span>
                    )}
                  </React.Fragment>
                ))}
              </div>

              {/* Right side */}
              <div className="flex items-center gap-3">
                {/* Refresh button */}
                <Button
                  variant="ghost"
                  size="icon"
                  className="bg-[#FFFFFF] dark:bg-[#1A1A1A] text-gray-700 hover:!text-gray-700 dark:text-gray-400 dark:hover:!text-gray-400 h-12 w-12 rounded-full hover:bg-gray-100 dark:hover:bg-[#252525]"
                  onClick={() => window.location.reload()}
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M21 2v6h-6"></path>
                    <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                    <path d="M3 22v-6h6"></path>
                    <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                  </svg>
                </Button>

                {/* Search button */}
                <Button
                  variant="ghost"
                  size="icon"
                  className="bg-[#FFFFFF] dark:bg-[#1A1A1A] text-gray-600 dark:text-gray-400 h-12 w-12 rounded-full hover:bg-gray-100 dark:hover:bg-[#252525]"
                  onClick={() => setIsSearchOpen(true)}
                >
                  <Search className="h-5 w-5 text-gray-700 dark:text-gray-400" />
                </Button>

                {/* Notifications */}
                <Link href="/notifications">
                  <Button variant="ghost" size="icon" className="bg-[#FFFFFF] dark:bg-[#1A1A1A] text-gray-600 dark:text-gray-400 h-12 w-12 rounded-full hover:bg-gray-100 dark:hover:bg-[#252525]">
                    {unreadCount > 0 ? (
                      <BellRing className="h-5 w-5" style={{ color: '#DF1C41' }} />
                    ) : (
                      <Bell className="h-5 w-5 text-gray-700 dark:text-gray-400" />
                    )}
                  </Button>
                </Link>

                {/* User dropdown */}
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <button
                      className="bg-[#FFFFFF] dark:bg-[#1A1A1A] hover:bg-gray-100 dark:hover:bg-[#252525] rounded-full pl-2 pr-4 h-12 flex items-center gap-3 cursor-pointer transition-colors"
                    >
                      <Avatar className="h-8 w-8">
                        <AvatarImage src={user.avatar} alt={user.name} />
                        <AvatarFallback className="bg-purple-500 text-white">
                          {user.name?.[0]?.toUpperCase() || "U"}
                        </AvatarFallback>
                      </Avatar>
                      <span className="text-sm font-medium text-gray-900 dark:text-white">
                        {user.name}
                      </span>
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-600 dark:text-gray-400">
                        <polyline points="6 9 12 15 18 9"></polyline>
                      </svg>
                    </button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" className="w-56 z-[9999] bg-white/[0.35] dark:bg-[#18191E]/[0.39] backdrop-blur-md rounded-2xl border-0 p-2" sideOffset={8}>
                    <DropdownMenuItem
                      onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
                      className="cursor-pointer flex items-center rounded-sm mb-1"
                    >
                      {theme === 'dark' ? <Sun className="mr-2 h-4 w-4" /> : <Moon className="mr-2 h-4 w-4" />}
                      <span>{theme === 'dark' ? 'Light mode' : 'Dark mode'}</span>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem asChild>
                      <Link href="/settings" className="cursor-pointer flex items-center rounded-sm mb-1">
                        <Settings className="mr-2 h-4 w-4" />
                        <span>Settings</span>
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                      <Link href="/support" className="cursor-pointer flex items-center rounded-sm mb-1">
                        <HelpCircle className="mr-2 h-4 w-4" />
                        <span>Support</span>
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                      <Link href="/legal" className="cursor-pointer flex items-center rounded-sm mb-1">
                        <Gavel className="mr-2 h-4 w-4" />
                        <span>Legal</span>
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem asChild>
                      <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="w-full cursor-pointer text-red-600 focus:text-red-600 flex items-center rounded-sm"
                      >
                        <LogOut className="mr-2 h-4 w-4" />
                        <span>Log out</span>
                      </Link>
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </header>
          </div>

          {/* Content Container */}
          <div className={cn("w-full overflow-hidden pt-16 sm:pt-20 lg:pt-0 p-4 lg:px-9 lg:py-4")}>
            <main className="w-full mt-14 lg:mt-0" data-sidebar-collapsed={!sidebarOpen}>
              {children}
            </main>
          </div>
        </div>
      </div>

      {/* <div 
          className="w-full h-full -z-10 bg-no-repeat bg-cover bg-center absolute bottom-0 left-1/2 -translate-x-1/2"
          style={{
            backgroundImage: theme === 'dark' ? `url(${blurBg})` : 'none',
          }}
        ></div> */}

        <div className="w-full h-full hidden dark:block -z-10 dark:bg-[rgb(56,41,128)] fixed bottom-0 left-1/2 -translate-x-1/2">
          <div
            className="fixed hidden dark:block bottom-0 left-1/2 -translate-x-1/2 w-full h-full"
            style={{
              background: "radial-gradient(800px 600px at 50% 900px, rgba(106, 75, 251, 0.6), rgba(0, 0, 0, 0))",
              filter: "blur(8rem)",
            }}
          ></div>
        </div>

      {enableDataUpdateNotifier && <DataUpdateNotifier />}
      <Toaster />
      <SearchModal isOpen={isSearchOpen} onClose={() => setIsSearchOpen(false)} />

        {/* // black purple with blur background */}
      {/* <div 
        className="w-full h-full -z-10 bg-no-repeat bg-cover absolute bottom-0 left-1/2 -translate-x-1/2"
        style={{
          backgroundColor: theme === 'dark' ? 'transparent' : '#FAFAF8',
          backgroundImage: theme === 'dark' ? `url(${blurBg})` : 'none',
        }}
      ></div> */}
    </div>
  )
}
