import React, { useState } from "react";
import { Head } from "@inertiajs/react";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import AppLayout from "@/Layouts/AppLayout";
import TrackCard from "@/components/TrackCard";
import TrackTable from "@/components/TrackTable";
import SimilarProducers from "@/components/SimilarProducers";
import RecommendedTracks from "@/components/RecommendedTracks";
import CollaboratingProducersTab from "@/components/CollaboratingProducersTab";
import ArtistCollaborations from "@/components/ArtistCollaborations";
import ProducerStatCard from "@/components/ProducerStatCard";
import {
    Music,
    Users,
    Mic2,
    Activity,
    Disc3,
    BarChart3,
    LayoutGrid,
    AlignJustify,
    Heart,
    Plus,
} from "lucide-react";
import StatsTab from "@/components/StatsTab";
import { Button } from "@/components/ui/button";
import { router } from "@inertiajs/react";
import StatCard from "@/components/StatCard";
import { cn } from "@/lib/utils";

export default function show({
    producer,
    recommendedTracks,
    auth,
    tracks,
    isFollowing,
    isFavourited,
    collaborators,
    artistCollaborators,
    collabArtists,
    similarProducers,
    stats,
}) {
    if (!producer) {
        return (
            <AppLayout user={auth?.user}>
                <div>
                    <Head title="Producer" />
                    <div className="flex items-center justify-center h-64">
                        <p className="text-muted-foreground">
                            Producer not found
                        </p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout user={auth?.user}>
            <Head title={producer?.name} />
            <div className="space-y-6">
                <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div className="flex items-center gap-4">
                        <img
                            src={producer?.image_url}
                            alt={`${producer?.name}'s profile`}
                            className="w-20 lg:w-40 lg:h-40 h-20 rounded-full object-cover"
                        />
                        <div>
                            <h1 className="text-4xl lg:text-7xl font-normal">
                                {producer?.name}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {producer?.followers_count}{" "}
                                {producer?.followers_count === 1
                                    ? "Follower"
                                    : "Followers"}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-4">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => router.post(route('producers.follow', producer.id))}
                            className="bg-[#FFFFFF] dark:bg-[#1A1A1A] text-gray-600 dark:text-gray-400 h-10 w-10 rounded-full hover:bg-gray-100 dark:hover:bg-[#252525] hover:text-gray-600"
                        >
                            <Heart
                                className={cn('h-4 w-4  dark:stroke-white', isFavourited ? 'fill-black dark:fill-white' : '')}
                            />
                        </Button>
                        <Button
                            variant="default"
                            size="sm"
                            onClick={() => router.post(route('producers.favourite', producer.id))}
                            className="bg-primary text-xs hover:bg-primary/90 text-primary-foreground py-5 !px-4 rounded-2xl"
                        >
                            <Plus className="stroke-3" />{" "}
                            {isFollowing ? "UNFOLLOW" : "FOLLOW"} PRODUCER
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <StatCard
                        title="Total Tracks"
                        value={
                            producer?.tracks?.length?.toString().slice(0, 2) ||
                            ""
                        }
                        valueGray={
                            producer?.tracks?.length.toString().slice(2) || ""
                        }
                        footer="Tracks produced"
                    />
                    <StatCard
                        title="Artist Collabs"
                        value={
                            artistCollaborators?.length
                                ?.toString()
                                ?.slice(0, 2) || ""
                        }
                        valueGray={
                            artistCollaborators?.length.toString().slice(2) ||
                            ""
                        }
                        footer="Unique artists"
                    />
                    <StatCard
                        title="Producer Collabs"
                        value={
                            collaborators?.length?.toString().slice(0, 2) || ""
                        }
                        valueGray={
                            collaborators?.length.toString().slice(2) || ""
                        }
                        footer="Collaborating producers"
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
                                value="collab-artists"
                                className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
                            >
                                <Mic2 className="h-4 w-4" /> Artists
                                Collaborations
                            </TabsTrigger>
                            <TabsTrigger
                                value="recommended-tracks"
                                className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
                            >
                                <Disc3 className="h-4 w-4" /> Recommended
                            </TabsTrigger>
                            <TabsTrigger
                                value="similar-producers"
                                className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
                            >
                                <Users className="h-4 w-4" /> Similar Producers
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
                        <div className="">
                            <TrackTable
                                tracks={producer?.tracks || []}
                                producerName={producer?.name}
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
                            {producer?.tracks.map((track) => (
                                <TrackCard
                                    key={track.id}
                                    track={track}
                                    variant="producer-page"
                                    viewMode="grid"
                                />
                            ))}
                        </div> */}
                    </TabsContent>

                    <TabsContent value="collab-producers" className="space-y-4">
                        <CollaboratingProducersTab
                            producers={collaborators}
                            producerId={producer?.id}
                        />
                    </TabsContent>

                    <TabsContent value="collab-artists" className="space-y-4">
                        <ArtistCollaborations
                            producerName={producer?.name}
                            producerId={producer?.id}
                            collaborators={artistCollaborators}
                        />
                    </TabsContent>

                    <TabsContent
                        value="recommended-tracks"
                        className="space-y-4"
                    >
                        <RecommendedTracks tracks={recommendedTracks} />
                    </TabsContent>

                    <TabsContent
                        value="similar-producers"
                        className="space-y-4"
                    >
                        <SimilarProducers similarProducers={similarProducers || []} />
                    </TabsContent>

                    <TabsContent value="stats" className="space-y-4">
                        <StatsTab
                            durationByGenre={stats.durationByGenre}
                            collaborationBreakdown={
                                stats.collaborationBreakdown
                            }
                            popularityDistribution={
                                stats.popularityDistribution
                            }
                            weeklyListeningData={stats.weeklyListeningData}
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
