import React from "react"
import { Link, usePage } from "@inertiajs/react"
import { Badge } from "@/components/ui/badge"
import { LayoutDashboard, Music, Users, Settings, LogOut, ChevronRight, Mic2, HelpCircle, Gavel, Bell,} from "lucide-react"
import { useTheme } from "next-themes"
import { useIsMobile } from "@/hooks/use-mobile"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarRail,
} from "@/components/ui/sidebar"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { cn } from "@/lib/utils"

interface AppSidebarProps {
  user: {
    name: string
    email: string
    avatar?: string
  }
}

export function AppSidebar({ user }: AppSidebarProps) {
  const { url, props } = usePage()
  const unreadCount = props.unreadCount || 0
  const [dropdownOpen, setDropdownOpen] = React.useState(false)
  const { theme } = useTheme()
  const isMobile = useIsMobile()


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
    {
      label: "Notifications",
      icon: Bell,
      href: "/notifications",
      count: unreadCount,
    },
  ]

  return (
    <Sidebar collapsible={isMobile ? "none" : "icon"} variant="inset">
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" asChild>
              <Link href="/dashboard" className="flex items-center gap-5">
                <img
                  src={theme === 'dark' ? '/images/Logo-Light.svg' : '/images/Logo-Dark.svg'}
                  alt="TrackCer"
                  className="size-8 flex-shrink-0"
                />
                <div className="grid flex-1 text-left text-sm leading-tight">
                  <span className="truncate font-semibold">TrackCer</span>
                  <span className="truncate text-xs text-muted-foreground">
                    Track The Producer
                  </span>
                </div>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent>
        <SidebarGroup>
          <SidebarGroupContent>
            <SidebarMenu>
              {navigation.map((item) => {
                const isActive = url.startsWith(item.href)
                const Icon = item.icon

                return (
                  <SidebarMenuItem key={item.href}>
                    <SidebarMenuButton asChild isActive={isActive}>
                      <Link href={item.href} className="relative flex items-center gap-2 w-full">
                        <Icon className="size-4 flex-shrink-0" />
                        <span className="truncate">{item.label}</span>
                        {item.count > 0 && (
                          <Badge className="ml-auto bg-red-500 text-white flex-shrink-0">{item.count}</Badge>
                        )}
                      </Link>
                    </SidebarMenuButton>
                  </SidebarMenuItem>
                )
              })}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarContent>
      <SidebarFooter>
        <SidebarMenu>
          <SidebarMenuItem className="relative">
            <DropdownMenu open={dropdownOpen} onOpenChange={setDropdownOpen}>
              <DropdownMenuTrigger asChild>
                <div className="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm hover:bg-muted">
                  <Avatar className="h-8 w-8 rounded-lg flex-shrink-0">
                    <AvatarImage src={user.avatar} alt={user.name} />
                    <AvatarFallback className="rounded-lg">
                      {user.name?.[0]?.toUpperCase() || "U"}
                    </AvatarFallback>
                  </Avatar>
                  <div className="grid flex-1 text-left text-sm leading-tight min-w-0">
                    <span className="truncate font-semibold">{user.name}</span>
                    <span className="truncate text-xs text-muted-foreground">
                      {user.email}
                    </span>
                  </div>
                  <ChevronRight className={cn(
                    "ml-auto size-4 transition-transform duration-200 flex-shrink-0",
                    dropdownOpen && "-rotate-90"
                  )} />
                </div>
              </DropdownMenuTrigger>

              <DropdownMenuContent
                className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg z-[9999]"
                side="top"
                align="end"
                sideOffset={4}
                collisionPadding={20}
                avoidCollisions={false}
              >
                <DropdownMenuItem asChild>
                  <Link href="/settings">
                    <Settings className="mr-2 size-4" />
                    Settings
                  </Link>
                </DropdownMenuItem>

                <DropdownMenuItem asChild>
                  <Link href="/support">
                    <HelpCircle className="mr-2 size-4" />
                    Support
                  </Link>
                </DropdownMenuItem>

                <DropdownMenuItem asChild>
                  <Link href="/legal">
                    <Gavel className="mr-2 size-4" />
                    Legal
                  </Link>
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                <DropdownMenuItem asChild>
                  <Link
                    method="post"
                    href="/logout"
                    as="button"
                    className="w-full text-red-600 focus:text-red-600"
                  >
                    <LogOut className="mr-2 size-4" />
                    Log out
                  </Link>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarFooter>
      <SidebarRail />
    </Sidebar>
  )
}
