import React, { useState } from "react";
import { Head, usePage, Link, router, useForm } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import Pagination from "@/components/Pagination";
import { Button } from "@/components/ui/button";
import TopArtistsChart from "@/components/TopArtistsChart";
import { format } from "date-fns";
import { Calendar } from "@/components/ui/calendar";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    MoveUpRight,
    Users,
    BarChart3,
    Search,
    Calendar as CalendarIcon,
    MicVocal,
} from "lucide-react";
import ArtistCard from "./ArtistCard";

export default function ArtistIndex({ auth }) {
    const {
        artistsData,
        searchQuery,
        topArtistsByRange,
        tab: initialTab,
        range,
    } = usePage().props;
    const [tab, setTab] = useState(initialTab || "list");
    const [selectedRange, setSelectedRange] = useState(range || "all");
    const [customStart, setCustomStart] = useState(null);
    const [customEnd, setCustomEnd] = useState(null);
    const [startDateOpen, setStartDateOpen] = useState(false);
    const [endDateOpen, setEndDateOpen] = useState(false);
    const [mobileStartDateOpen, setMobileStartDateOpen] = useState(false);
    const [mobileEndDateOpen, setMobileEndDateOpen] = useState(false);

    const { data, setData, get } = useForm({
        search: searchQuery || "",
    });

    const handleSearch = (e) => {
        e.preventDefault();
        get(route("artists", { per_page: 16 }), {
            preserveState: true,
        });
    };

    const handleRangeChange = (value) => {
        setSelectedRange(value);
        if (value === "custom") return;
        router.get(
            route("artists"),
            { range: value, tab: "second" },
            {
                preserveScroll: true,
                preserveState: true,
                only: ["topArtistsByRange"],
            }
        );
    };

    const applyCustomRange = () => {
        if (customStart && customEnd) {
            router.get(
                route("artists"),
                {
                    range: "custom",
                    start: format(customStart, "yyyy-MM-dd"),
                    end: format(customEnd, "yyyy-MM-dd"),
                    tab: "second",
                },
                {
                    preserveScroll: true,
                    preserveState: true,
                    only: ["topArtistsByRange"],
                }
            );
        }
    };

    return (
        <AppLayout user={auth?.user}>
            <Head title="Artists" />
            <div className="space-y-6">
                <h1 className="text-3xl lg:text-5xl font-normal">Artists</h1>
                <Tabs defaultValue={tab} value={tab} onValueChange={setTab}>
                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                        {/* Tabs Container */}
                        <div className="flex items-center justify-between w-full gap-2">
                            <div className="overflow-x-auto scrollbar-hide">
                                <TabsList className="w-max h-auto p-0.5 bg-white/[0.51] dark:bg-[#191919]/[0.58] rounded-full flex flex-nowrap">
                                    <TabsTrigger
                                        value="list"
                                        className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
                                    >
                                        <MicVocal className="h-4 w-4" /> Artists
                                    </TabsTrigger>
                                    <TabsTrigger
                                        value="second"
                                        className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
                                    >
                                        <BarChart3 className="h-4 w-4" /> Stats
                                    </TabsTrigger>
                                </TabsList>
                            </div>
                        </div>

                        {/* Search Bar for Desktop - Only show on list tab */}
                        {tab === "list" && (
                            <div className="hidden lg:block">
                                <form
                                    onSubmit={handleSearch}
                                    className="relative flex items-center"
                                >
                                    <input
                                        type="text"
                                        value={data.search}
                                        onChange={(e) =>
                                            setData("search", e.target.value)
                                        }
                                        placeholder="Search artists by name..."
                                        className="bg-white/[0.58] dark:bg-[#191919]/[0.58] rounded-full pl-16 pr-32 py-3.5 h-[52px] max-w-[600px] lg:w-[450px] xl:w-[600px] focus:outline-none focus:ring-2 focus:ring-purple-500 placeholder:text-gray-500 dark:placeholder:text-gray-400 text-gray-900 dark:text-white"
                                    />
                                    <div className="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#E4E4E4] dark:bg-[#1A1A1A] flex items-center justify-center">
                                        <Search className="w-5 h-5 text-gray-600 dark:text-gray-400" />
                                    </div>
                                    <Button
                                        type="submit"
                                        className="absolute right-2 top-1/2 -translate-y-1/2 bg-[#6A4BFB] hover:bg-[#6A4BFB]/90 text-white rounded-full px-6 py-2 h-10"
                                    >
                                        Search
                                    </Button>
                                </form>
                            </div>
                        )}

                        {/* Date Picker for Stats Tab - Desktop */}
                        {tab === "second" && (
                            <div className="hidden lg:flex items-center gap-4">
                                {/* Range Dropdown */}
                                <div className="p-0.5 bg-white/[0.51] dark:bg-[#191919]/[0.58] rounded-full">
                                    <Select
                                        onValueChange={handleRangeChange}
                                        value={selectedRange}
                                    >
                                        <SelectTrigger className="h-auto py-3 sm:py-5.5 px-4 sm:px-6 w-60 bg-transparent border-0 rounded-full text-sm sm:text-base font-medium">
                                            <CalendarIcon className="h-4 w-4 mr-2 text-gray-600 dark:text-gray-400" />
                                            <SelectValue placeholder="Select Range" />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white/[0.51] dark:bg-[#191919]/[0.58] backdrop-blur-md rounded-2xl border-0 p-2">
                                            <SelectItem
                                                value="all"
                                                className="rounded-sm data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white mb-1"
                                            >
                                                All Time
                                            </SelectItem>
                                            <SelectItem
                                                value="today"
                                                className="rounded-sm data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white mb-1"
                                            >
                                                Today
                                            </SelectItem>
                                            <SelectItem
                                                value="week"
                                                className="rounded-sm data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white mb-1"
                                            >
                                                This Week
                                            </SelectItem>
                                            <SelectItem
                                                value="last7"
                                                className="rounded-sm data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white mb-1"
                                            >
                                                Last 7 Days
                                            </SelectItem>
                                            <SelectItem
                                                value="custom"
                                                className="rounded-sm data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white"
                                            >
                                                Custom Range
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {selectedRange === "custom" && (
                                    <>
                                        {/* Start Date Picker */}
                                        <Popover
                                            open={startDateOpen}
                                            onOpenChange={setStartDateOpen}
                                        >
                                            <PopoverTrigger>
                                                <button
                                                    type="button"
                                                    className="h-[48px] px-6 rounded-full bg-white/[0.51] dark:bg-[#191919]/[0.58] border-0 hover:bg-white/70 dark:hover:bg-[#191919]/70 text-gray-900 dark:text-gray-100 transition-colors whitespace-nowrap"
                                                >
                                                    {customStart
                                                        ? format(
                                                              customStart,
                                                              "PPP"
                                                          )
                                                        : "Pick Start Date"}
                                                </button>
                                            </PopoverTrigger>
                                            <PopoverContent
                                                className="w-auto p-0 bg-white/[0.51] dark:bg-[#191919]/[0.58] backdrop-blur-md rounded-2xl border-0"
                                                align="start"
                                            >
                                                <Calendar
                                                    mode="single"
                                                    selected={customStart}
                                                    onSelect={(date) => {
                                                        setCustomStart(date);
                                                        setStartDateOpen(false);
                                                    }}
                                                />
                                            </PopoverContent>
                                        </Popover>

                                        {/* End Date Picker */}
                                        <Popover
                                            open={endDateOpen}
                                            onOpenChange={setEndDateOpen}
                                        >
                                            <PopoverTrigger>
                                                <button
                                                    type="button"
                                                    className="h-[48px] px-6 rounded-full bg-white/[0.51] dark:bg-[#191919]/[0.58] border-0 hover:bg-white/70 dark:hover:bg-[#191919]/70 text-gray-900 dark:text-gray-100 transition-colors whitespace-nowrap"
                                                >
                                                    {customEnd
                                                        ? format(
                                                              customEnd,
                                                              "PPP"
                                                          )
                                                        : "Pick End Date"}
                                                </button>
                                            </PopoverTrigger>
                                            <PopoverContent
                                                className="w-auto p-0 bg-white/[0.51] dark:bg-[#191919]/[0.58] backdrop-blur-md rounded-2xl border-0"
                                                align="start"
                                            >
                                                <Calendar
                                                    mode="single"
                                                    selected={customEnd}
                                                    onSelect={(date) => {
                                                        setCustomEnd(date);
                                                        setEndDateOpen(false);
                                                    }}
                                                />
                                            </PopoverContent>
                                        </Popover>

                                        {/* Apply Button */}
                                        <Button
                                            onClick={applyCustomRange}
                                            disabled={
                                                !customStart || !customEnd
                                            }
                                            className="h-[48px] rounded-full bg-[#6A4BFB] hover:bg-[#6A4BFB]/90 border-0 text-white"
                                        >
                                            Apply
                                        </Button>
                                    </>
                                )}
                            </div>
                        )}
                    </div>

                    <TabsContent value="list" className="space-y-4">
                        {/* Mobile Search Bar */}
                        <div className="lg:hidden mb-6">
                            <form
                                onSubmit={handleSearch}
                                className="relative flex items-center"
                            >
                                <input
                                    type="text"
                                    value={data.search}
                                    onChange={(e) =>
                                        setData("search", e.target.value)
                                    }
                                    placeholder="Search artists by name..."
                                    className="bg-white/[0.58] dark:bg-[#191919]/[0.58] rounded-full pl-14 pr-4 py-3 h-[48px] w-full focus:outline-none focus:ring-2 focus:ring-purple-500 placeholder:text-gray-500 dark:placeholder:text-gray-400 text-gray-900 dark:text-white"
                                />
                                <div className="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-[#E4E4E4] dark:bg-[#1A1A1A] flex items-center justify-center">
                                    <Search className="w-4 h-4 text-gray-600 dark:text-gray-400" />
                                </div>
                            </form>
                        </div>
                        {artistsData.data.length === 0 && (
                            <div className="w-full text-center py-10 text-muted-foreground">
                                <p>No artists available.</p>
                            </div>
                        )}            
                        <div className="grid gap-4 grid-cols-1 md:grid-cols-[repeat(auto-fill,minmax(340px,1fr))]">
                            {artistsData.data.length > 0 && artistsData.data.map((data) => (
                                <ArtistCard
                                    key={data.artist_id}
                                    artist={data}
                                />
                            ))}
                        </div>

                        <div className="mt-6">
                            <Pagination
                                meta={artistsData.meta}
                                only={["artistsData"]}
                            />
                        </div>
                    </TabsContent>

                    <TabsContent value="second" className="space-y-4">
                        {/* Mobile Date Picker */}
                        <div className="lg:hidden">
                            <div className="p-0.5 bg-white/[0.51] dark:bg-[#191919]/[0.58] rounded-full inline-flex">
                                <Select
                                    onValueChange={handleRangeChange}
                                    value={selectedRange}
                                >
                                    <SelectTrigger className="h-auto py-3 px-4 w-47 bg-transparent border-0 rounded-full text-sm font-medium">
                                        <CalendarIcon className="h-4 w-4 mr-2 text-gray-600 dark:text-gray-400" />
                                        <SelectValue placeholder="Select Range" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white/[0.51] dark:bg-[#191919]/[0.58] backdrop-blur-md rounded-2xl border-0 p-2">
                                        <SelectItem
                                            value="all"
                                            className="rounded-xl data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white mb-1"
                                        >
                                            All Time
                                        </SelectItem>
                                        <SelectItem
                                            value="today"
                                            className="rounded-xl data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white mb-1"
                                        >
                                            Today
                                        </SelectItem>
                                        <SelectItem
                                            value="week"
                                            className="rounded-xl data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white mb-1"
                                        >
                                            This Week
                                        </SelectItem>
                                        <SelectItem
                                            value="last7"
                                            className="rounded-xl data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white mb-1"
                                        >
                                            Last 7 Days
                                        </SelectItem>
                                        <SelectItem
                                            value="custom"
                                            className="rounded-xl data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white"
                                        >
                                            Custom Range
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {selectedRange === "custom" && (
                                <div className="flex flex-wrap gap-2 mt-4">
                                    {/* Start Date Picker */}
                                    <Popover
                                        open={mobileStartDateOpen}
                                        onOpenChange={setMobileStartDateOpen}
                                    >
                                        <PopoverTrigger>
                                            <button
                                                type="button"
                                                className="h-[44px] px-6 rounded-full bg-white/[0.51] dark:bg-[#191919]/[0.58] border-0 hover:bg-white/70 dark:hover:bg-[#191919]/70 text-gray-900 dark:text-gray-100 text-sm transition-colors whitespace-nowrap"
                                            >
                                                {customStart
                                                    ? format(
                                                          customStart,
                                                          "MMM d"
                                                      )
                                                    : "Start Date"}
                                            </button>
                                        </PopoverTrigger>
                                        <PopoverContent
                                            className="w-auto p-0 bg-white dark:bg-[#191919] backdrop-blur-md rounded-2xl border-0"
                                            align="start"
                                        >
                                            <Calendar
                                                mode="single"
                                                selected={customStart}
                                                onSelect={(date) => {
                                                    setCustomStart(date);
                                                    setMobileStartDateOpen(
                                                        false
                                                    );
                                                }}
                                            />
                                        </PopoverContent>
                                    </Popover>

                                    {/* End Date Picker */}
                                    <Popover
                                        open={mobileEndDateOpen}
                                        onOpenChange={setMobileEndDateOpen}
                                    >
                                        <PopoverTrigger>
                                            <button
                                                type="button"
                                                className="h-[44px] px-6 rounded-full bg-white/[0.51] dark:bg-[#191919]/[0.58] border-0 hover:bg-white/70 dark:hover:bg-[#191919]/70 text-gray-900 dark:text-gray-100 text-sm transition-colors whitespace-nowrap"
                                            >
                                                {customEnd
                                                    ? format(customEnd, "MMM d")
                                                    : "End Date"}
                                            </button>
                                        </PopoverTrigger>
                                        <PopoverContent
                                            className="w-auto p-0 bg-white dark:bg-[#191919] backdrop-blur-md rounded-2xl border-0"
                                            align="start"
                                        >
                                            <Calendar
                                                mode="single"
                                                selected={customEnd}
                                                onSelect={(date) => {
                                                    setCustomEnd(date);
                                                    setMobileEndDateOpen(false);
                                                }}
                                            />
                                        </PopoverContent>
                                    </Popover>

                                    {/* Apply Button */}
                                    <Button
                                        onClick={applyCustomRange}
                                        disabled={!customStart || !customEnd}
                                        className="h-[44px] rounded-full bg-[#6A4BFB] hover:bg-[#6A4BFB]/90 border-0 text-white text-sm"
                                    >
                                        Apply
                                    </Button>
                                </div>
                            )}
                        </div>

                        <TopArtistsChart
                            topArtists={topArtistsByRange || []}
                            start="2025-07-21"
                            end="2025-07-28"
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
