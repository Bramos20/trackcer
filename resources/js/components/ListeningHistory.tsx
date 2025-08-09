import React from "react";
import { Button } from "@/components/ui/button";
import { router } from "@inertiajs/react";
import { useTheme } from "next-themes";
import TrackCard1 from "./TrackCard1";
export default function ListeningHistory({history}) {

    const getTrackData = (track) => {
        const trackData =
            typeof track.track_data === "string"
                ? JSON.parse(track.track_data)
                : track.track_data || {};

        let albumImage = "https://via.placeholder.com/300";
        let trackUrl = null;
        let year = null;

        if (track.source === "spotify") {
            albumImage = trackData.album?.images?.[0]?.url ?? albumImage;
            trackUrl = trackData?.external_urls?.spotify;
            if (trackData.album?.release_date) {
                year = trackData.album.release_date.substring(0, 4);
            }
        } else if (track.source === "Apple Music") {
            const artwork = trackData.attributes?.artwork;
            if (artwork?.url) {
                albumImage = artwork.url
                    .replace("{w}", Math.max(300, artwork.width || 300))
                    .replace("{h}", Math.max(300, artwork.height || 300));
            }
            trackUrl = trackData?.attributes?.url;
            if (trackData.attributes?.releaseDate) {
                year = trackData.attributes.releaseDate.substring(0, 4);
            }
        }

        return { albumImage, trackUrl, year };
    };

    if (!history || history.length === 0) {
        return (
            <div className="w-full flex items-center justify-center h-64">
                <p className="text-muted-foreground">No recent listening history</p>
            </div>
        );
    }

    return (
        <div className="w-full">
            <div className="mb-6">
                <h2 className="text-lg sm:text-2xl">Recent Listening History</h2>
                {/*<p className="text-muted-foreground">Your latest tracks</p>*/}
            </div>

            {/* Grid layout similar to top producers */}
            <div className="grid grid-cols-1 md:grid-cols-[repeat(auto-fill,minmax(340px,1fr))] gap-6 pb-4">
                {history.slice(0, 10).map((item, index) => (
                    <TrackCard1 key={index} track={item} layout="grid" />
                ))}
            </div>

            {/* Load More Button */}
            <div className="flex justify-end sm:justify-end items-center mt-6 sm:mt-10 relative z-10">
                <Button
                    onClick={() => router.visit('/tracks')}
                    className="bg-primary hover:bg-primary/90 text-primary-foreground font-medium text-sm tracking-wide flex items-center justify-center cursor-pointer"
                    style={{
                        backgroundColor: '#6A4BFB',
                        width: '220px',
                        height: '55px',
                        borderRadius: '22px'
                    }}
                >
                    LOAD MORE
                </Button>
            </div>
        </div>
    );
}
