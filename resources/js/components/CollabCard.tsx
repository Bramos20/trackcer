import React from "react";

import { Button } from "@/components/ui/button";
// import { Link, NavLink } from "react-router";
import { Eye, MoveUpRight } from "lucide-react";
import { Badge } from "./ui/badge";
import { cn } from "@/lib/utils";
import { router } from "@inertiajs/react";

interface ProducerCollabData {
    id: number;
    artist_name?: string;
    name?: string; // Alternative field name
    image_url?: string;
    track_count?: number;
    collaboration_count?: number; // Alternative field name
    latest_track?: {
        track_name: string;
    };
    genres?: string[];
    role?: string;
    slug?: string;
}

type ProducerCollabCardProps = {
    producer: ProducerCollabData;
    href?: string;
    isProducer?: boolean;
    producerId?: string;
    artistName?: string;
};

export default function CollabCard({
    producer,
    href,
    isProducer = false,
    producerId = "",
    artistName = "",
}: ProducerCollabCardProps) {
    console.log("id", producerId);
    return (
        <div
            className={`singleCard w-full h-full flex flex-col bg-[#FFFFFF] dark:bg-[#19191950] rounded-[44px]`}
            style={{
                // borderBottomRightRadius: "0rem",
                backdropFilter: "blur(40px)",
                WebkitMask:
                    'url("/assets/bottom-right.png") center / contain no-repeat, linear-gradient(#000000 0 0)',
                maskSize: "11rem 6rem",
                maskPosition: "bottom right",
                maskComposite: "exclude",
            }}
        >
            <div className="relative z-10 p-3.5 h-full flex flex-col">
                {/* Content wrapper that grows */}
                <div className="flex-1">
                    {/* Header with image and name */}
                    <div className="flex justify-between gap-5 flex-wrap mb-6">
                        <div className="flex items-center flex-wrap gap-4">
                            {producer?.image_url ? (
                                <img
                                    src={producer?.image_url}
                                    alt={
                                        producer?.artist_name || producer?.name
                                    }
                                    className="md:w-20 md:h-20 w-16 h-16 rounded-full object-cover border-2 border-white/20"
                                />
                            ) : (
                                <div className="md:w-20 md:h-20 w-16 h-16 rounded-full bg-gray-300 dark:bg-gray-700 border-2 border-white/20" />
                            )}
                            <div className="flex-1">
                                <h3 className="text-xl md:text-2xl font-semibold break-words font-montserrat">
                                    {producer?.artist_name || producer?.name}
                                </h3>
                                <p className="text-gray-600 dark:text-gray-400 font-montserrat">
                                    {producer?.role || "Producer"}
                                </p>
                            </div>
                        </div>
                    </div>
                    {/* Total Stats */}
                    <div className="mb-3">
                        <h4 className="text-base font-semibold mb-3">
                            Total Stats:
                        </h4>
                        <div className="flex items-center justify-between gap-4 p-2.5 rounded-lg bg-[rgb(242,242,242)] dark:bg-white/[0.12]">
                            <div className="flex-1 pl-2">
                                <p className="text-lg md:text-xl font-normal">
                                    {producer?.track_count ||
                                        producer?.collaboration_count || producer?.shared_tracks_count}{" "}
                                    Shared{" "}
                                    <span className="text-lg font-normal text-gray-600 dark:text-gray-400">
                                        Tracks
                                    </span>
                                </p>
                            </div>
                            <button
                                onClick={() => {
                                    if (artistName) {
                                        // When called from artist page, use the artist shared tracks route
                                        router.visit(
                                            route(
                                                "producer.artistSharedTracks",
                                                [producerId, artistName]
                                            )
                                        );
                                    } else {
                                        // When called from producer page, use the producer shared tracks route
                                        router.visit(
                                            route("producer.sharedTracks", [
                                                producerId,
                                                producer.id,
                                            ])
                                        );
                                    }
                                }}
                                className="w-10 h-10 flex items-center justify-center rounded-full bg-white dark:bg-[#2D2B3C] hover:opacity-80 transition-opacity"
                                aria-label="View shared tracks"
                            >
                                <Eye className="w-5 h-5 text-black dark:text-white" />
                            </button>
                        </div>
                    </div>

                    {/* Latest Track */}
                    <div className="mb-3">
                        <h4 className="text-base mb-2">Latest Track</h4>
                        {producer?.latest_track ? (
                            <p className="text-gray-600 dark:text-gray-400">
                                {producer?.latest_track.track_name}
                            </p>
                        ) : (
                            <p className="text-gray-400 dark:text-gray-500">
                                No recent track available
                            </p>
                        )}
                    </div>

                    {/* Genres */}
                    <div className="mb-3 font-montserrat">
                        <h4 className="text-base font-semibold mb-2">Genres</h4>
                        <div className="flex flex-wrap gap-2 max-h-[75px] md:max-h-[105px] overflow-y-auto">
                            {producer?.genres?.length ? (
                                <>
                                    {producer.genres
                                        .slice(0, 8)
                                        .map((genre: string, i: number) => (
                                            <span
                                                key={i}
                                                className="px-3 py-1 rounded-full text-xs md:text-sm"
                                                style={{
                                                    backgroundColor:
                                                        "rgba(234, 97, 21, 0.12)",
                                                }}
                                            >
                                                {genre}
                                            </span>
                                        ))}
                                </>
                            ) : (
                                <p className="text-gray-400 dark:text-gray-500 text-sm">
                                    No genres available
                                </p>
                            )}
                        </div>
                    </div>
                </div>
                {isProducer ? (
                    <div className="flex justify-end py-4">
                        <Button
                            onClick={() =>
                                router.post(
                                    route(
                                        "producers.createPlaylist",
                                        producer.id
                                    )
                                )
                            }
                            className="px-6 text-white text-sm font-normal"
                            style={{
                                backgroundColor: "#EA6115",
                                height: "22px",
                                borderRadius: "20px",
                            }}
                        >
                            Create Playlist
                        </Button>
                    </div>
                ) : // <div className="mt-2 md:mt-3 flex justify-end">
                //     <Badge className="px-6 text-white text-sm font-normal bg-[#EA6115] rounded-full cursor-pointer">
                //         {!isProducer
                //             ? "Add to Playlist"
                //             : "Create Playlist"}
                //     </Badge>
                // </div>

                null}

                {/* View Producer Button */}
                <div className="mt-3">
                    <Button
                        onClick={() => {
                            if (isProducer) {
                                router.visit(route("producer.show", producer.id));
                            } else {
                                router.visit(route("artist.show", producer.artist_name || producer.name));
                            }
                        }}
                        className={cn(
                            "text-white flex items-center justify-center gap-1.5 sm:gap-2 font-medium font-inter text-sm tracking-wide bg-[#6A4BFB] w-full h-[55px] rounded-[22px] z-10",
                            isProducer
                                ? "max-w-[calc(100%-6.4rem)]"
                                : "max-w-[calc(100%-6.4rem)]"
                        )}
                    >
                        <MoveUpRight className="w-4 h-4" />
                        <span className="truncate text-[13px] sm:text-sm">
                            {isProducer ? "VIEW PRODUCER" : "VIEW ARTIST"}
                        </span>
                    </Button>
                </div>
            </div>
        </div>
    );
}
