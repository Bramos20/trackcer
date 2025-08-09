import React, { useState, useMemo } from "react";
import { Button } from "@/components/ui/button";
import { Play } from "lucide-react";
import musicImg from "@/assets/1.jpg";
import { Card, CardContent, CardHeader, CardTitle } from "./ui/card";

interface Track {
    id: number;
    track_name: string;
    album_name: string;
    artist_name: string;
    genres?: any;
    source: string;
    popularity_spotify?: number;
    popularity_apple_music?: number;
    track_data?: any;
    popularity_data?: any;
}

interface TrackTableProps {
    tracks: Track[];
    producerName: string;
}

export default function TrackTable({
    tracks = [],
    producerName,
}: TrackTableProps) {
    const [showAll, setShowAll] = useState(false);

    // Debug logging
    console.log("TrackTable props:", { tracks, producerName });

    if (!tracks || tracks.length === 0) {
        return (
            <div className="relative bg-[#F4F4F4]/[0.86] dark:bg-[#191919]/[0.51] rounded-[27.35px] sm:rounded-[40px] p-6 pt-2 sm:pt-5 lg:pt-6 sm:p-8">
                <h2 className="text-xl sm:text-2xl font-normal mb-1 md:mb-2">
                    Your Tracks
                </h2>
                <p className="text-muted-foreground">No tracks found</p>
            </div>
        );
    }

    // Parse and sort tracks by popularity
    const sortedTracks = useMemo(() => {
        return [...tracks].sort((a, b) => {
            const getPopularity = (track: Track) => {
                if (track.source === "spotify") {
                    const trackData =
                        typeof track.track_data === "string"
                            ? JSON.parse(track.track_data)
                            : track.track_data || {};
                    return trackData.popularity || 0;
                } else if (track.source === "Apple Music") {
                    const popularityData =
                        typeof track.popularity_data === "string"
                            ? JSON.parse(track.popularity_data)
                            : track.popularity_data || {};
                    return popularityData.popularity || 0;
                }
                return 0;
            };

            return getPopularity(b) - getPopularity(a);
        });
    }, [tracks]);

    const displayedTracks = showAll ? sortedTracks : sortedTracks.slice(0, 5);

    const getTrackInfo = (track: Track) => {
        const trackData =
            typeof track.track_data === "string"
                ? JSON.parse(track.track_data)
                : track.track_data || {};
        const popularityData =
            typeof track.popularity_data === "string"
                ? JSON.parse(track.popularity_data)
                : track.popularity_data || {};

        let albumImage = "https://via.placeholder.com/50";
        let trackUrl = null;
        let popularity = 0;

        if (track.source === "spotify") {
            albumImage = trackData.album?.images?.[0]?.url ?? albumImage;
            trackUrl = trackData?.external_urls?.spotify;
            popularity = trackData.popularity || 0;
        } else if (track.source === "Apple Music") {
            const artwork = trackData.attributes?.artwork;
            if (artwork?.url) {
                albumImage = artwork.url.replace("{w}", 50).replace("{h}", 50);
            }
            trackUrl = trackData.attributes?.url;
            popularity = popularityData.popularity || 0;
        }

        return { albumImage, trackUrl, popularity };
    };

    return (
        <Card className="rounded-[31.19px] lg:rounded-[47px] px-3 sm:px-6 py-2 sm:py-4">
            <CardHeader className="px-3 py-3.5 pb-1 sm:pb-3">
                <div>
                    <CardTitle className="md:text-2xl font-normal">
                        Popular
                    </CardTitle>
                </div>
            </CardHeader>
            <CardContent className="px-3 pt-0 pb-0">
                <div className="overflow-auto">
                    <div className="flex flex-1 rounded-[15px]">
                        <div className="min-w-[40px] text-sm md:text-lg text-muted-foreground py-[14px] px-2 rounded-l-[15px] flex items-center justify-center bg-[rgba(255,255,255,0.75)] dark:bg-[rgba(0,0,0,0.12)]"></div>
                        <div className="min-w-[200px] text-sm md:text-lg text-muted-foreground py-[14px] px-4 flex items-center flex-1 bg-[rgba(255,255,255,0.75)] dark:bg-[rgba(0,0,0,0.12)]">
                            Song title
                        </div>
                        <div className="min-w-[200px] text-sm md:text-lg text-muted-foreground py-[14px] px-4 flex items-center flex-1 bg-[rgba(255,255,255,0.75)] dark:bg-[rgba(0,0,0,0.12)]">
                            Album
                        </div>
                        <div className="min-w-[200px] text-sm md:text-lg text-muted-foreground py-[14px] px-4 flex items-center flex-1 bg-[rgba(255,255,255,0.75)] dark:bg-[rgba(0,0,0,0.12)]">
                            Artist
                        </div>
                        <div className="min-w-[200px] text-sm md:text-lg text-muted-foreground py-[14px] px-4 flex items-center flex-1 bg-[rgba(255,255,255,0.75)] dark:bg-[rgba(0,0,0,0.12)]">
                            Genre
                        </div>
                        <div className="min-w-[200px] text-sm md:text-lg text-muted-foreground py-[14px] px-4 flex items-center flex-1 bg-[rgba(255,255,255,0.75)] dark:bg-[rgba(0,0,0,0.12)]">
                            Popularity
                        </div>
                        <div className="min-w-[100px] py-[14px] px-2 rounded-r-[15px] flex items-center justify-end bg-[rgba(255,255,255,0.75)] dark:bg-[rgba(0,0,0,0.12)]"></div>
                    </div>

                    {/* Songs List */}
                    <div>
                        {displayedTracks.map((track, index) => {
                            const { albumImage, trackUrl, popularity } =
                                getTrackInfo(track);
                            let genreName = "Unknown";
                            if (
                                track.genres &&
                                Array.isArray(track.genres) &&
                                track.genres.length > 0
                            ) {
                                genreName =
                                    track.genres[0].name || track.genres[0];
                            } else if (
                                track.genres &&
                                typeof track.genres === "string"
                            ) {
                                genreName = track.genres.split(",")[0];
                            }
                            return (
                                <div
                                    key={track.id}
                                    className="group transition-colors duration-150"
                                >
                                    <div className="flex flex-1 gap-0 py-4 items-center">
                                        <div className="min-w-[40px] px-2 flex items-center justify-center">
                                            <span className="text-sm md:text-lg text-muted-foreground">
                                                {index + 1}
                                            </span>
                                        </div>
                                        <div className="min-w-[200px] flex items-center space-x-3 px-4 flex-1">
                                            <img
                                                src={
                                                    albumImage ||
                                                    "/placeholder.svg"
                                                }
                                                alt={track.album_name}
                                                className="w-10 h-10 !rounded-[2px] lg:!rounded-[4px] object-cover"
                                            />
                                            <p className="text-sm md:text-lg">
                                                {track.track_name}
                                            </p>
                                        </div>
                                        <div className="min-w-[200px] px-4 flex-1">
                                            <p className="text-sm md:text-lg text-muted-foreground">
                                                {track.album_name}
                                            </p>
                                        </div>
                                        <div className="min-w-[200px] px-4 flex-1">
                                            <p className="text-sm md:text-lg text-muted-foreground">
                                                {track.artist_name}
                                            </p>
                                        </div>
                                        <div className="min-w-[200px] px-4 flex-1">
                                            <p className="text-sm md:text-lg text-muted-foreground">
                                                {genreName}
                                            </p>
                                        </div>
                                        <div className="min-w-[200px] px-4 flex-1">
                                            <div className="relative w-full h-4 bg-white dark:bg-white/10 max-w-[174px] rounded-[5px] overflow-hidden">
                                                <div
                                                    className="absolute inset-y-0 left-0 rounded-[5px]"
                                                    style={{
                                                        width: `${popularity}%`,
                                                        backgroundColor:
                                                            "#EA6115",
                                                        backgroundImage:
                                                            "repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0,0,0,.1) 10px, rgba(0,0,0,.1) 20px)",
                                                    }}
                                                >
                                                    <span className="absolute right-2 top-1/2 -translate-y-1/2 text-white font-normal text-sm">
                                                        {popularity}%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="min-w-[100px] px-2 flex justify-end">
                                            {trackUrl && (
                                                <a
                                                    href={trackUrl}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex hover:scale-110 transition-transform"
                                                >
                                                    {track.source ===
                                                    "spotify" ? (
                                                        <img
                                                            src="/images/Primary_Logo_Green_RGB.svg"
                                                            alt="Spotify"
                                                            className="w-6 h-6"
                                                        />
                                                    ) : (
                                                        <img
                                                            src="/images/Apple_Music_icon.svg"
                                                            alt="Apple Music"
                                                            className="w-6 h-6"
                                                        />
                                                    )}
                                                </a>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* See More Button */}
                    {sortedTracks.length > 5 && !showAll && (
                        <div className="px-6 py-4">
                            <button
                                className="text-sm text-muted-foreground"
                                onClick={() => setShowAll(true)}
                            >
                                SEE MORE
                            </button>
                        </div>
                    )}
                </div>
            </CardContent>
            {/* <CardFooter className="text-sm md:text-lg text-muted-foreground px-6 py-2">
        Showing data for: {rangeLabel}
      </CardFooter> */}
        </Card>
    );
}
