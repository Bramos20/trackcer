import React, { useState, useEffect } from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { BarChart3, Search } from "lucide-react";
import TrackCard from "@/components/TrackCard";
import Pagination from "@/components/Pagination";
import DailyListenChart from "@/components/DailyListenChart";
import TrackCard1 from "@/components/TrackCard1";
import musicImg from "@/assets/1.jpg";
import { DateRange } from "react-day-picker";
import { DateRangePicker } from "@/components/ui/date-range-picker";
import { cn } from "@/lib/utils";


export default function Tracks({
    tracks,
    auth,
    searchQuery,
    listensPerDay
}) {
    const [tab, setTab] = useState("list");
    const { get, data, setData } = useForm({
        search: searchQuery || "",
    });
    const { url } = usePage();

    // Get the track ID from URL parameters
    const urlParams = new URLSearchParams(url.split("?")[1] || "");
    const selectedTrackId = urlParams.get("track");
    const [, setDateRange] = useState<DateRange | undefined>({
        from: undefined,
        to: undefined,
    });

    // Scroll to selected track on mount
    useEffect(() => {
        if (selectedTrackId && tab === "list") {
            const element = document.getElementById(`track-${selectedTrackId}`);
            if (element) {
                element.scrollIntoView({ behavior: "smooth", block: "center" });
                // Add a highlight effect
                element.classList.add(
                    "ring-2",
                    "ring-purple-500",
                    "ring-offset-2"
                );
                setTimeout(() => {
                    element.classList.remove(
                        "ring-2",
                        "ring-purple-500",
                        "ring-offset-2"
                    );
                }, 3000);
            }
        }
    }, [selectedTrackId, tab]);

    const handleSearch = (e) => {
        e.preventDefault();
        get(route("tracks.index"), {
            preserveState: false, // Ensure fresh data when searching
        });
    };

    return (
        <AppLayout user={auth?.user}>
            <Head title="All Tracks" />
            <div className="space-y-6 min-h-0">
                <h1 className="text-3xl font-normal">All Listened Tracks</h1>

                <Tabs value={tab} onValueChange={setTab}>
                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                        {/* Tabs Container */}
                        <div
                            className={cn(
                                "flex flex-col md:flex-row md:items-center justify-between gap-2 w-full z-10",
                                tab === "graph" && listensPerDay?.length > 0
                                    ? "max-w-[calc(100%)] lg:max-w-[calc(100%-265px)]"
                                    : "max-w-[calc(100%)]"
                            )}
                        >
                            <div className="overflow-x-auto scrollbar-hide">
                                <TabsList className="w-max h-auto p-0.5 bg-white/[0.51] dark:bg-[#191919]/[0.58] rounded-full flex flex-nowrap">
                                    <TabsTrigger
                                        value="list"
                                        className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
                                    >
                                        Track List
                                    </TabsTrigger>
                                    <TabsTrigger
                                        value="graph"
                                        className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
                                    >
                                        <BarChart3 className="h-4 w-4" /> Graph
                                    </TabsTrigger>
                                </TabsList>
                            </div>
                            {tab === "graph" && (
                                <div className="flex items-center gap-2 md:gap-4">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="bg-[#FFFFFF] dark:bg-[#1A1A1A] text-gray-600 dark:text-gray-400 h-[35px] md:h-[52px] w-[35px] md:w-[52px] rounded-full hover:bg-gray-100 dark:hover:bg-[#252525] hover:text-gray-600"
                                        onClick={() =>
                                            setDateRange({
                                                from: undefined,
                                                to: undefined,
                                            })
                                        }
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="20"
                                            height="20"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                        >
                                            <path d="M21 2v6h-6"></path>
                                            <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                                            <path d="M3 22v-6h6"></path>
                                            <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                                        </svg>
                                    </Button>

                                    <DateRangePicker
                                        // defaultValue={{
                                        //     from: new Date(2024, 10, 1),
                                        //     to: new Date(2024, 11, 1),
                                        // }}
                                        onChange={(range) => {
                                            console.log(
                                                "Selected range:",
                                                range
                                            );
                                            console.log(
                                                "start",
                                                range?.from,
                                                "end",
                                                range?.to
                                            );
                                        }}
                                    />
                                </div>
                            )}
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
                                        placeholder="Search by track name, artist, or album..."
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
                    </div>

                    <TabsContent value="list" className="space-y-4">
                        {/* Mobile Search Bar */}
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
                                    placeholder="Search by track name, artist, or album..."
                                    className="bg-white/[0.58] dark:bg-[#191919]/[0.58] rounded-full pl-14 pr-4 py-3 h-[48px] w-full focus:outline-none focus:ring-2 focus:ring-purple-500 placeholder:text-gray-500 dark:placeholder:text-gray-400 text-gray-900 dark:text-white"
                                />
                                <div className="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-[#E4E4E4] dark:bg-[#1A1A1A] flex items-center justify-center">
                                    <Search className="w-4 h-4 text-gray-600 dark:text-gray-400" />
                                </div>
                            </form>
                        </div>

                        {tracks.data.length > 0 ? (
                            <div
                                className={`tracksGrid grid grid-cols-1 md:grid-cols-[repeat(auto-fill,minmax(340px,1fr))] gap-6 mb-20`}
                            >
                                {tracks.data.map((track, index) => (
                                    <TrackCard1
                                        key={track.id}
                                        track={track}
                                        layout="grid"
                                        index={index}
                                    />
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-10 text-muted-foreground">
                                <p>No tracks found.</p>
                            </div>
                        )}

                        <div className="mt-1">
                            <Pagination meta={tracks.meta} only={["tracks"]} />
                        </div>
                    </TabsContent>

                    <TabsContent value="graph" className="space-y-4">
                        <div>
                            {listensPerDay?.length > 0 ? (
                                <DailyListenChart
                                    listensPerDay={listensPerDay}
                                />
                            ) : (
                                <div className="text-muted-foreground text-center p-8">
                                    <p>No listening data to display.</p>
                                </div>
                            )}
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
