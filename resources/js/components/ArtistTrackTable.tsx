import React, { useState, useMemo } from "react";
import { Button } from "@/components/ui/button";
import { Play } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "./ui/card";

interface Producer {
    id: number;
    name: string;
}

interface Track {
    id: number;
    track_name: string;
    album_name: string;
    artist_name: string;
    source: string;
    play_count?: number;
    track_data?: any;
    producers: Producer[];
}

interface ArtistTrackTableProps {
    tracks: Track[];
    artistName: string;
}

export default function ArtistTrackTable({
    tracks = [],
    artistName,
}: ArtistTrackTableProps) {
    const [showAll, setShowAll] = useState(false);

    if (!tracks || tracks.length === 0) {
        return (
            <div className="relative bg-[#F4F4F4]/[0.86] dark:bg-[#191919]/[0.51] rounded-[27.35px] sm:rounded-[40px] p-6 pt-2 sm:pt-5 lg:pt-6 sm:p-8">
                <h2 className="text-xl sm:text-2xl font-normal mb-1 md:mb-2">
                    Songs
                </h2>
                <p className="text-muted-foreground">No tracks found</p>
            </div>
        );
    }

    // Sort tracks by play count (descending)
    const sortedTracks = useMemo(() => {
        return [...tracks].sort((a, b) => {
            const playCountA = a.play_count || 0;
            const playCountB = b.play_count || 0;
            return playCountB - playCountA;
        });
    }, [tracks]);

    const displayedTracks = showAll ? sortedTracks : sortedTracks.slice(0, 5);

    const getTrackInfo = (track: Track) => {
        const trackData =
            typeof track.track_data === "string"
                ? JSON.parse(track.track_data)
                : track.track_data || {};

        let albumImage = "https://via.placeholder.com/50";
        let trackUrl = null;

        if (track.source === "spotify") {
            albumImage = trackData.album?.images?.[0]?.url ?? albumImage;
            trackUrl = trackData?.external_urls?.spotify;
        } else if (track.source === "Apple Music") {
            const artwork = trackData.attributes?.artwork;
            if (artwork?.url) {
                albumImage = artwork.url.replace("{w}", 50).replace("{h}", 50);
            }
            trackUrl = trackData.attributes?.url;
        }

        return { albumImage, trackUrl };
    };

    return (
        <Card className="rounded-[31.19px] lg:rounded-[47px] px-3 sm:px-6 py-2 sm:py-4">
      <CardHeader className="px-3 py-3.5 pb-1 sm:pb-3">
        <div>
          <CardTitle className="md:text-2xl font-normal">Songs</CardTitle>
        </div>
      </CardHeader>
<CardContent className="px-3 pt-0 pb-0">
        <div className="overflow-auto">
          <div className="flex-1 flex rounded-[15px] ">
            <div className="min-w-[40px] text-sm md:text-lg text-muted-foreground py-[14px] dark:bg-[rgba(0,0,0,0.12)] bg-[rgba(255,255,255,0.75)] rounded-l-[15px]"></div>
            <div className="min-w-[200px] text-sm md:text-lg px-4 text-muted-foreground flex-1 py-[14px] dark:bg-[rgba(0,0,0,0.12)] bg-[rgba(255,255,255,0.75)]">
              Song title
            </div>
            <div className="min-w-[200px] text-sm md:text-lg px-6 md:px-8 text-muted-foreground flex-1 py-[14px] dark:bg-[rgba(0,0,0,0.12)] bg-[rgba(255,255,255,0.75)]">
              Album
            </div>
            <div className="min-w-[200px] text-sm md:text-lg pl-9 md:pl-6 text-muted-foreground flex-1 py-[14px] dark:bg-[rgba(0,0,0,0.12)] bg-[rgba(255,255,255,0.75)]">
              Producer
            </div>
            <div className="min-w-[200px] text-sm md:text-lg pl-9 md:pl-6 text-muted-foreground flex-1 py-[14px] dark:bg-[rgba(0,0,0,0.12)] bg-[rgba(255,255,255,0.75)]">
              Times Played
            </div>
            <div className="min-w-[100px] text-sm font-medium py-[14px] dark:bg-[rgba(0,0,0,0.12)] bg-[rgba(255,255,255,0.75)] rounded-r-[15px]"></div>
          </div>

          {/* Songs List */}
          <div>
            {displayedTracks.map((track, index) => {
                const { albumImage, trackUrl } =
                                getTrackInfo(track);
                return (
                    <div
                key={track.id}
                className="group transition-colors duration-150"
              >
                {/* header Layout */}
                <div className="flex flex-1 gap-3 py-4 items-center">
                  <div className="min-w-[40px]  px-2 flex items-center justify-center">
                    <span className="text-sm md:text-lg text-muted-foreground">
                      {index + 1}
                    </span>
                  </div>
                  <div className="min-w-[200px] flex items-center space-x-3 flex-1">
                    <div className="flex-shrink-0">
                      <img
                        src={albumImage || "/placeholder.svg"}
                        alt={`${track.album_name} artwork`}
                        className="w-10 h-10 !rounded-[2px] lg:!rounded-[4px] object-cover"
                      />
                    </div>
                    <div>
                      <p className="text-sm md:text-lg">{track.track_name}</p>
                    </div>
                  </div>
                  <div className="min-w-[200px] flex-1">
                    <p className="text-sm md:text-lg text-muted-foreground">
                      {track.album_name}
                    </p>
                  </div>
                  <div className="min-w-[200px] flex-1">
                    <p className="text-sm md:text-lg text-muted-foreground">
                      {track.producers?.length
                                                ? track.producers
                                                      .map(
                                                          (p: Producer) =>
                                                              p.name
                                                      )
                                                      .join(", ")
                                                : "Unknown"}
                    </p>
                  </div>
                   <div className="min-w-[200px] flex-1">
                    <p className="text-sm md:text-lg text-muted-foreground">
                      {track.play_count || 0}
                    </p>
                  </div>
                  <div className="min-w-[50px] flex justify-end">
                    {trackUrl && (
                                            <a
                                                href={trackUrl}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex hover:scale-110 transition-transform"
                                            >
                                                {track.source === "spotify" ? (
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
                )
            })}
          </div>

          {/* See More Button */}
          {sortedTracks.length > 5 && !showAll && (
            <div className="px-6 py-4">
              <button
                onClick={() => setShowAll(true)}
                className="text-sm text-muted-foreground"
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
