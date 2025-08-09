import React  from "react";
import type { ArtistStat } from "@/types";
import { MoveUpRight } from "lucide-react";
import { Link } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
// import { MoveUpRight } from "lucide-react";
type ArtistProps = {
    artist: ArtistStat;
};

export default function ArtistCard({ artist }: ArtistProps) {
    return (
        <div
            className={`singleCard w-full h-full flex flex-col bg-[rgba(255,255,255,0.51)] dark:bg-[#19191950] rounded-[44px]`}
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
                            {artist.image_url ? (
                                <img
                                    src={artist.image_url}
                                    alt={artist.artist_name}
                                    className="w-[88px] h-[88px] rounded-full object-cover border-2 border-white/20"
                                />
                            ) : (
                                <div className="w-20 h-20 rounded-full bg-gray-300 dark:bg-gray-700 border-2 border-white/20" />
                            )}
                            <div className="flex-1">
                                <h3 className="text-xl md:text-2xl font-semibold break-words font-montserrat">
                                    {artist.artist_name}
                                </h3>
                                <p className="text-gray-600 dark:text-gray-400 font-montserrat">
                                    {artist.role || "Producer"}
                                </p>
                            </div>
                        </div>
                    </div>
                    {/* Total Stats */}
                    <div className="mb-3">
                        <h4 className="text-base mb-3">Total Stats:</h4>
                        <div className="flex items-center gap-4 p-3 rounded-lg bg-white/[0.64] dark:bg-white/[0.12]">
                            <div className="flex-1 pl-2">
                                <p className="text-lg font-normal">
                                    {artist.track_count}{" "}
                                    <span className="text-lg font-normal text-gray-600 dark:text-gray-400">
                                        Tracks
                                    </span>
                                </p>
                            </div>
                            <div className="w-px h-10 bg-gray-300 dark:bg-gray-600"></div>
                            <div className="flex-1 pl-2">
                                <p className="text-lg font-normal">
                                    {artist.total_minutes}{" "}
                                    <span className="text-lg font-normal text-gray-600 dark:text-gray-400">
                                        Minutes
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Average Popularity */}
                    <div className="mb-3">
                        <h4 className="text-base mb-2">Average Popularity</h4>
                        <div className="relative w-full h-8 bg-white dark:bg-white/10 rounded-[10px] overflow-hidden">
                            <div
                                className="absolute inset-y-0 left-0 rounded-[10px]"
                                style={{
                                    width: `${artist.average_popularity}%`,
                                    backgroundColor: "#EA6115",
                                    backgroundImage:
                                        "repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0,0,0,.1) 10px, rgba(0,0,0,.1) 20px)",
                                }}
                            >
                                <span className="absolute right-2 top-1/2 -translate-y-1/2 text-white font-normal text-sm">
                                    {artist.average_popularity}%
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Latest Track */}
                    <div className="mb-3">
                        <h4 className="text-base mb-2">Latest Track</h4>
                        {artist.latest_track ? (
                            <p className="text-gray-600 dark:text-gray-400">
                                {artist.latest_track.track_name}
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
                            {artist.genres?.length ? (
                                <>
                                    {artist.genres
                                        .slice(0, 8)
                                        .map((genre, i) => (
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
                {/* View Producer Button */}
                <div className="mt-5">
                    <Button
                        asChild
                        className="text-white font-medium font-inter text-sm tracking-wide bg-[#6A4BFB] max-w-[calc(100%-6.4rem)] w-full h-[55px] rounded-[22px] z-10"
                    >
                        <Link
                            href={route("artist.show", {
                                artist_name: artist.artist_name,
                            })}
                            className="flex items-center justify-center gap-2"
                        >
                            <MoveUpRight className="w-4 h-4" />
                            <span className="truncate text-sm sm:text-sm">
                                VIEW ARTIST
                            </span>
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    );
}
