import React, { useState, useEffect } from "react";
import { Link, usePage } from "@inertiajs/react";
import {
    LayoutDashboard,
    Music,
    Users,
    BarChart2,
    Menu,
    Settings,
    LogOut,
    ChevronDown,
    X,
    MoreHorizontal,
    Sun,
    Moon,
} from "lucide-react";
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
// Theme hook removed - now handled in app-header

const navigation = [
    { 
        label: "Dashboard", 
        icon: LayoutDashboard, 
        href: "/dashboard",
        badge: null
    },
    { 
        label: "Tracks", 
        icon: Music, 
        href: "/tracks",
        badge: null
    },
    { 
        label: "Producers", 
        icon: Users, 
        href: "/producers",
        badge: null
    },
    { 
        label: "Artists", 
        icon: Users, 
        href: "/artists",
        badge: null
    },
];

export default function Sidebar({ collapsed = false, onCollapse, user }) {
    const { url } = usePage();
    const [theme, setTheme] = useState<"light" | "dark">("dark");

    useEffect(() => {
        const savedTheme = localStorage.getItem("theme") || "dark";
        setTheme(savedTheme as "light" | "dark");
        document.documentElement.classList.toggle("dark", savedTheme === "dark");
    }, []);

    const toggleTheme = () => {
        const newTheme = theme === "dark" ? "light" : "dark";
        setTheme(newTheme);
        localStorage.setItem("theme", newTheme);
        document.documentElement.classList.toggle("dark", newTheme === "dark");
    };

    return (
        <aside
            className={cn(
                "fixed inset-y-0 left-0 z-40 hidden lg:flex flex-col",
                "bg-sidebar border-r border-sidebar-border",
                "transition-all duration-300 ease-in-out",
                collapsed ? "w-[68px]" : "w-[280px]"
            )}
        >
            {/* Header */}
            <div className="flex h-16 items-center border-b border-sidebar-border px-4">
                <div className={cn("flex items-center", collapsed ? "justify-center" : "justify-between w-full")}>
                    <Link href="/dashboard" className="flex items-center gap-2 group">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                            <img
                                src="/images/Favicon.svg"
                                alt="TrackCer"
                                className="h-5 w-5"
                            />
                        </div>
                        {!collapsed && (
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-semibold">TrackCer</span>
                                <span className="truncate text-xs text-sidebar-muted-foreground">Track The Producer</span>
                            </div>
                        )}
                    </Link>
                </div>
            </div>

            {/* Navigation */}
            <div className="flex-1 overflow-auto py-2">
                <nav className="grid gap-1 px-2">
                    <TooltipProvider delayDuration={0}>
                        {navigation.map((item) => {
                            const isActive = url.startsWith(item.href);
                            const Icon = item.icon;

                            const content = (
                                <Link
                                    href={item.href}
                                    className={cn(
                                        "flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition-all hover:bg-sidebar-accent hover:text-sidebar-accent-foreground",
                                        isActive 
                                            ? "bg-sidebar-accent text-sidebar-accent-foreground" 
                                            : "text-sidebar-foreground"
                                    )}
                                >
                                    <Icon className="h-4 w-4" />
                                    {!collapsed && (
                                        <>
                                            <span className="flex-1">{item.label}</span>
                                            {item.badge && (
                                                <Badge 
                                                    variant="secondary" 
                                                    className="ml-auto h-5 text-xs"
                                                >
                                                    {item.badge}
                                                </Badge>
                                            )}
                                        </>
                                    )}
                                </Link>
                            );

                            return collapsed ? (
                                <Tooltip key={item.href}>
                                    <TooltipTrigger asChild>
                                        {content}
                                    </TooltipTrigger>
                                    <TooltipContent side="right">
                                        <p>{item.label}</p>
                                    </TooltipContent>
                                </Tooltip>
                            ) : (
                                <div key={item.href}>
                                    {content}
                                </div>
                            );
                        })}
                    </TooltipProvider>
                </nav>
            </div>

            {/* Footer */}
            <div className="mt-auto border-t border-sidebar-border p-2">
                {/* Theme Toggle */}
                <div className="mb-2">
                    {collapsed ? (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={toggleTheme}
                                    className="h-8 w-8 mx-auto"
                                >
                                    {theme === 'dark' ? (
                                        <Sun className="h-4 w-4" />
                                    ) : (
                                        <Moon className="h-4 w-4" />
                                    )}
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="right">
                                <p>Toggle theme</p>
                            </TooltipContent>
                        </Tooltip>
                    ) : (
                        <Button
                            variant="ghost"
                            onClick={toggleTheme}
                            className="w-full justify-start h-8 px-3"
                        >
                            {theme === 'dark' ? (
                                <Sun className="h-4 w-4 mr-2" />
                            ) : (
                                <Moon className="h-4 w-4 mr-2" />
                            )}
                            Toggle theme
                        </Button>
                    )}
                </div>

                {/* User menu */}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            className={cn(
                                "w-full justify-start h-auto p-2",
                                collapsed && "justify-center"
                            )}
                        >
                            <div className="flex items-center gap-2">
                                <Avatar className="h-8 w-8">
                                    <AvatarImage src={user.avatar} alt={user.name} />
                                    <AvatarFallback className="bg-sidebar-primary text-sidebar-primary-foreground">
                                        {user.name?.[0]?.toUpperCase() ?? "U"}
                                    </AvatarFallback>
                                </Avatar>
                                {!collapsed && (
                                    <>
                                        <div className="grid flex-1 text-left text-sm leading-tight">
                                            <span className="truncate font-semibold">{user.name}</span>
                                            <span className="truncate text-xs text-sidebar-muted-foreground">
                                                {user.email}
                                            </span>
                                        </div>
                                        <ChevronDown className="ml-auto h-4 w-4" />
                                    </>
                                )}
                            </div>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent 
                        className="w-56" 
                        align={collapsed ? "center" : "end"}
                        side={collapsed ? "right" : "top"}
                    >
                        <div className="px-2 py-1.5 text-sm font-semibold">
                            {user.name}
                        </div>
                        <div className="px-2 py-1.5 text-xs text-muted-foreground">
                            {user.email}
                        </div>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href="/profile" className="cursor-pointer">
                                <Settings className="h-4 w-4 mr-2" />
                                Settings
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link 
                                method="post" 
                                href="/logout" 
                                as="button" 
                                className="w-full cursor-pointer text-red-600 focus:text-red-600"
                            >
                                <LogOut className="h-4 w-4 mr-2" />
                                Log out
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </aside>
    );
}

/* Mobile sidebar version */
export function MobileSidebar({ user }) {
    const [open, setOpen] = useState(false);
    const { url } = usePage();
    const [theme, setTheme] = useState<"light" | "dark">("dark");

    useEffect(() => {
        const savedTheme = localStorage.getItem("theme") || "dark";
        setTheme(savedTheme as "light" | "dark");
        document.documentElement.classList.toggle("dark", savedTheme === "dark");
    }, []);

    const toggleTheme = () => {
        const newTheme = theme === "dark" ? "light" : "dark";
        setTheme(newTheme);
        localStorage.setItem("theme", newTheme);
        document.documentElement.classList.toggle("dark", newTheme === "dark");
    };

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button 
                    variant="ghost" 
                    size="icon" 
                    className="lg:hidden h-9 w-9"
                >
                    <Menu className="h-4 w-4" />
                    <span className="sr-only">Toggle menu</span>
                </Button>
            </SheetTrigger>
            <SheetContent 
                side="left" 
                className="p-0 w-[280px] bg-sidebar border-sidebar-border"
            >
                <div className="flex h-full flex-col">
                    {/* Header */}
                    <div className="flex h-16 items-center border-b border-sidebar-border px-4">
                        <Link href="/dashboard" className="flex items-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                <img
                                    src="/images/Favicon.svg"
                                    alt="TrackCer"
                                    className="h-5 w-5"
                                />
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-semibold">TrackCer</span>
                                <span className="truncate text-xs text-sidebar-muted-foreground">Track The Producer</span>
                            </div>
                        </Link>
                    </div>

                    {/* Navigation */}
                    <div className="flex-1 overflow-auto py-2">
                        <nav className="grid gap-1 px-2">
                            {navigation.map((item) => {
                                const isActive = url.startsWith(item.href);
                                const Icon = item.icon;

                                return (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        onClick={() => setOpen(false)}
                                        className={cn(
                                            "flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition-all hover:bg-sidebar-accent hover:text-sidebar-accent-foreground",
                                            isActive 
                                                ? "bg-sidebar-accent text-sidebar-accent-foreground" 
                                                : "text-sidebar-foreground"
                                        )}
                                    >
                                        <Icon className="h-4 w-4" />
                                        <span className="flex-1">{item.label}</span>
                                        {item.badge && (
                                            <Badge 
                                                variant="secondary" 
                                                className="ml-auto h-5 text-xs"
                                            >
                                                {item.badge}
                                            </Badge>
                                        )}
                                    </Link>
                                );
                            })}
                        </nav>
                    </div>

                    {/* Footer */}
                    <div className="mt-auto border-t border-sidebar-border p-2">
                        {/* Theme Toggle */}
                        <Button
                            variant="ghost"
                            onClick={toggleTheme}
                            className="w-full justify-start h-8 px-3 mb-2"
                        >
                            {theme === 'dark' ? (
                                <Sun className="h-4 w-4 mr-2" />
                            ) : (
                                <Moon className="h-4 w-4 mr-2" />
                            )}
                            Toggle theme
                        </Button>

                        {/* User profile */}
                        <div className="flex items-center gap-2 p-2 rounded-lg bg-sidebar-accent/50">
                            <Avatar className="h-8 w-8">
                                <AvatarImage src={user.avatar} alt={user.name} />
                                <AvatarFallback className="bg-sidebar-primary text-sidebar-primary-foreground">
                                    {user.name?.[0]?.toUpperCase() ?? "U"}
                                </AvatarFallback>
                            </Avatar>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-semibold">{user.name}</span>
                                <span className="truncate text-xs text-sidebar-muted-foreground">
                                    {user.email}
                                </span>
                            </div>
                        </div>

                        <div className="mt-2 space-y-1">
                            <Link 
                                href="/profile" 
                                onClick={() => setOpen(false)}
                                className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground transition-all"
                            >
                                <Settings className="h-4 w-4" />
                                Settings
                            </Link>
                            <Link 
                                method="post" 
                                href="/logout" 
                                as="button" 
                                onClick={() => setOpen(false)}
                                className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-all w-full text-left"
                            >
                                <LogOut className="h-4 w-4" />
                                Log out
                            </Link>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}