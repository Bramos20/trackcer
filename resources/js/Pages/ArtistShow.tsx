import React from "react";
import { Head } from "@inertiajs/react";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import AppLayout from "@/Layouts/AppLayout";
import ArtistTrackTable from "@/components/ArtistTrackTable";
import CollaboratingProducersTab from "@/components/CollaboratingProducersTab";
import { Music, Users, BarChart3, Heart, Plus } from "lucide-react";
import TopProducersChart from "@/components/TopProducersCharts";
import StatCard from "@/components/StatCard";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

export default function Show({
    artist,
    auth,
    tracks,
    producerCollaborators,
    currentProducerId,
    topProducersForArtist,
}) {

        if (!artist) {
            return (
                <AppLayout user={auth?.user}>
                    <div>
                        <Head title="Producer" />
                        <div className="flex items-center justify-center h-64">
                            <p className="text-muted-foreground">
                                Artist not found
                            </p>
                        </div>
                    </div>
                </AppLayout>
            );
        }
    return (
        <AppLayout user={auth?.user}>
            <Head title={artist?.name} />

            <div className="space-y-6">
                <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div className="flex items-center gap-4">
                        <img
                            src={artist?.image_url}
                            alt={`${artist?.name}'s profile`}
                            className="w-20 lg:w-40 lg:h-40 h-20 rounded-full object-cover"
                        />
                        <div>
                            <h1 className="text-4xl lg:text-7xl font-normal">
                                {artist?.name}
                            </h1>
                        </div>
                    </div>
                    <div className="flex gap-4">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="bg-[#FFFFFF] dark:bg-[#1A1A1A] text-gray-600 dark:text-gray-400 h-10 w-10 rounded-full hover:bg-gray-100 dark:hover:bg-[#252525] hover:text-gray-600"
                        >
                            <Heart
                                className={cn(
                                    "h-4 w-4  dark:stroke-white",
                                    false
                                        ? "fill-black dark:fill-white"
                                        : ""
                                )}
                            />
                        </Button>
                        <Button
                            variant="default"
                            size="sm"
                            className="bg-primary text-xs hover:bg-primary/90 text-primary-foreground py-5 !px-4 rounded-2xl"
                        >
                            <Plus className="stroke-3" /> FOLLOW ARTIST
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <StatCard
                        title="Total Tracks"
                        value={tracks?.length?.toString()?.slice(0, 2)}
                        valueGray={tracks?.length?.toString()?.slice(2)}
                        footer="Tracks by artist"
                    />
                    <StatCard
                        title="Producer Collabs"
                        footer="Unique producers"
                        value={
                            producerCollaborators?.length
                                ?.toString()
                                ?.slice(0, 2) || ""
                        }
                        valueGray={
                            producerCollaborators?.length
                                ?.toString()
                                ?.slice(2) || ""
                        }
                    />
                </div>

                <Tabs defaultValue="tracks" className="w-full">
                    <div className="overflow-x-auto scrollbar-hide">
                        <TabsList className="mb-4 w-max h-auto p-0.5 bg-white/[0.51] dark:bg-[#191919]/[0.58] rounded-full flex flex-nowrap">
                            <TabsTrigger
                                value="tracks"
                                className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
                            >
                                <Music className="h-4 w-4" /> Tracks
                            </TabsTrigger>
                            <TabsTrigger
                                value="collab-producers"
                                className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
                            >
                                <Users className="h-4 w-4" /> Producers
                                Collaborations
                            </TabsTrigger>
                            <TabsTrigger
                                value="stats"
                                className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
                            >
                                <BarChart3 className="h-4 w-4" /> Stats
                            </TabsTrigger>
                        </TabsList>
                    </div>

                    <TabsContent value="tracks" className="space-y-4">
                        {/* Desktop view - Table */}
                        <div className="block">
                            <ArtistTrackTable
                                tracks={tracks || []}
                                artistName={artist?.name}
                            />
                        </div>

                        {/* Mobile view - Cards */}
                        {/* <div
                            className="md:hidden grid -space-y-12"
                            style={{
                                gridTemplateColumns: "repeat(auto-fit, 355px)",
                                justifyContent: "space-between",
                                columnGap: "20px",
                            }}
                        >
                            {tracks.map((track) => (
                                <TrackCard1
                                    key={track.id}
                                    track={track}
                                    variant="artist-page"
                                    viewMode="grid"
                                />
                            ))}
                        </div> */}
                    </TabsContent>

                    <TabsContent value="collab-producers" className="space-y-4">
                        <CollaboratingProducersTab
                            producers={producerCollaborators}
                            producerId={currentProducerId}
                            artistName={artist?.name}
                        />
                    </TabsContent>

                    <TabsContent value="stats" className="space-y-4">
                        <TopProducersChart producers={topProducersForArtist} />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
