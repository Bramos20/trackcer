import React, { useState } from "react";
import { Head, usePage, Link, router, useForm } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import Pagination from "@/components/Pagination";
import { Button } from "@/components/ui/button";
import TopProducersChart from "@/components/TopProducersChart";
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
import ProducerFiltersSidebar from "@/components/ProducerFiltersSidebar";
import {
    ListFilter,
    MoveUpRight,
    Users,
    Heart,
    BarChart3,
    Search,
    Calendar as CalendarIcon,
} from "lucide-react";
import ProducerCard from "@/components/ProducerCard";
import { cn } from "@/lib/utils";


export default function ProducerIndex({
    auth,
    genres,
    selectedGenre,
    filters,
    producer,
    favouriteProducers, 
}) {
    const {
        producersData, 
        searchQuery,
        topProducersByRange,
        weeklyTopProducers,
        range,
    } = usePage().props;
    const [tab, setTab] = useState("list");
    const [selectedRange, setSelectedRange] = useState(range || "all");
    const [customStart, setCustomStart] = useState(null);
    const [customEnd, setCustomEnd] = useState(null);
    const [isFilterOpen, setIsFilterOpen] = useState(false);
    const [startDateOpen, setStartDateOpen] = useState(false);
    const [endDateOpen, setEndDateOpen] = useState(false);
    const [mobileStartDateOpen, setMobileStartDateOpen] = useState(false);
    const [mobileEndDateOpen, setMobileEndDateOpen] = useState(false);

    const { data, setData, get } = useForm({
        search: searchQuery || "",
    });

    const handleSearch = (e) => {
        e.preventDefault();
        get(route("producers"), {
            preserveState: false, // Ensure fresh data when searching
        });
    };

    const handleRangeChange = (value) => {
        setSelectedRange(value);

        if (value === "custom") return;
        router.get(
            route("producers"),
            { range: value },
            { preserveScroll: true, preserveState: true }
        );
    };

    const applyCustomRange = () => {
        if (customStart && customEnd) {
            router.get(
                route("producers"),
                {
                    range: "custom",
                    start: format(customStart, "yyyy-MM-dd"),
                    end: format(customEnd, "yyyy-MM-dd"),
                },
                { preserveScroll: true, preserveState: true }
            );
        }
    };

    return (
        <AppLayout user={auth?.user}>
            <Head title="Producers" />
            <div className="space-y-6">
                <h1 className="text-3xl lg:text-5xl font-normal">
                    Producers Stats
                </h1>
                <Tabs defaultValue={tab} value={tab} onValueChange={setTab}>
                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                        {/* Tabs Container */}
                        <div className="flex items-center justify-between w-full gap-2 flex-wrap">
                            <div className="overflow-x-auto scrollbar-hide">
                                <TabsList className="w-max h-auto p-0.5 bg-white/[0.51] dark:bg-[#191919]/[0.58] rounded-full flex flex-nowrap">
                                    <TabsTrigger
                                        value="list"
                                        className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
                                    >
                                        <Users className="h-4 w-4" /> Producers
                                    </TabsTrigger>
                                    {/* <TabsTrigger
                                        value="favourites"
                                        className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
                                    >
                                        <Heart className="h-4 w-4" /> Favourites
                                    </TabsTrigger> */}
                                    <TabsTrigger
                                        value="second"
                                        className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
                                    >
                                        <BarChart3 className="h-4 w-4" /> Stats
                                    </TabsTrigger>
                                </TabsList>
                            </div>
                            <div
                                className={cn(
                                    "block lg:hidden",
                                    tab === "second" && "hidden"
                                )}
                            >
                                <button
                                    onClick={() =>
                                        setIsFilterOpen(!isFilterOpen)
                                    }
                                    className="px-6 py-3 sm:py-4 bg-white/[0.51] dark:bg-[#191919]/[0.58] rounded-[27px] hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex-shrink-0 flex items-center gap-2"
                                >
                                    <ListFilter className="w-5 h-5" />
                                </button>
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
                                        placeholder="Search producers by name..."
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
                                        defaultValue="all"
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
                        {/* Mobile Search Bar and Filter */}
                        <div className="lg:hidden mb-6 flex items-center gap-2">
                            <form
                                onSubmit={handleSearch}
                                className="relative flex items-center flex-1"
                            >
                                <input
                                    type="text"
                                    value={data.search}
                                    onChange={(e) =>
                                        setData("search", e.target.value)
                                    }
                                    placeholder="Search producers by name..."
                                    className="bg-white/[0.58] dark:bg-[#191919]/[0.58] rounded-full pl-14 pr-4 py-3 h-[48px] w-full focus:outline-none focus:ring-2 focus:ring-purple-500 placeholder:text-gray-500 dark:placeholder:text-gray-400 text-gray-900 dark:text-white"
                                />
                                <div className="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-[#E4E4E4] dark:bg-[#1A1A1A] flex items-center justify-center">
                                    <Search className="w-4 h-4 text-gray-600 dark:text-gray-400" />
                                </div>
                            </form>
                        </div>

                        <div className="flex flex-col lg:flex-row gap-6">
                            {/* Sidebar Filters */}
                            <div
                                className={`w-full lg:w-[258px] lg:mr-5 flex-shrink-0 ${
                                    isFilterOpen ? "block" : "hidden lg:block"
                                }`}
                            >
                                <ProducerFiltersSidebar
                                    genres={genres}
                                    selectedGenre={selectedGenre}
                                    filters={filters}
                                    isOpen={true}
                                />
                            </div>

                            {/* Main Content */}
                            <div className="flex-1 min-w-0">
                                {producersData.data.length === 0 && (
                                    <div className="w-full text-center py-10 text-muted-foreground">
                                        <p>No producers available.</p>
                                    </div>
                                )}
                                {/* Producer Cards Grid */}
                                <div className="grid gap-6 grid-cols-1 md:grid-cols-[repeat(auto-fill,minmax(340px,1fr))]">
                                    {producersData.data.length > 0 && producersData.data.map((data) => (
                                        <ProducerCard
                                            key={data?.producer?.id}
                                            producer={data}
                                        />
                                    ))}
                                </div>

                                <div className="mt-12">
                                    <Pagination
                                        meta={producersData.meta}
                                        only={["producersData"]}
                                    />
                                </div>
                            </div>
                        </div>
                    </TabsContent>

                    <TabsContent value="favourites" className="space-y-4">
                        <div className="flex flex-col lg:flex-row gap-6">
                            {/* Sidebar Filters */}
                            <div
                                className={`w-full lg:w-72 flex-shrink-0 ${
                                    isFilterOpen ? "block" : "hidden lg:block"
                                }`}
                            >
                                <ProducerFiltersSidebar
                                    genres={genres}
                                    selectedGenre={selectedGenre}
                                    filters={filters}
                                    isOpen={true}
                                />
                            </div>

                            {/* Main Content */}
                            <div className="flex-1 min-w-0">
                                <div className="grid gap-6 grid-cols-1 md:grid-cols-[repeat(auto-fill,minmax(340px,1fr))]">
                                    {favouriteProducers?.data?.map((data) => (
                                        <ProducerCard
                                            key={data?.producer?.id}
                                            producer={data}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    </TabsContent>

                    <TabsContent value="second" className="space-y-4">
                        {/* Mobile Date Picker */}
                        <div className="lg:hidden">
                            <div className="p-0.5 bg-white/[0.51] dark:bg-[#191919]/[0.58] rounded-full inline-flex">
                                <Select
                                    onValueChange={handleRangeChange}
                                    defaultValue="all"
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

                        {/* Charts */}
                        <TopProducersChart
                            topProducers={topProducersByRange || []}
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
