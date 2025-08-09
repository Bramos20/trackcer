import React from "react";
import { cn } from "@/lib/utils";
import type { Track } from "@/types";
import { useTheme } from "next-themes";
import MarqueeText from "./MarqueeText";

type TrackProps = {
    track: Track;
    layout?: string;
    rootClass?: string;
    rtc?: boolean;
};


export default function TrackCard1({
    track,
    layout = "grid",
    rootClass,
    rtc = false,
}: TrackProps) {
    const { theme } = useTheme();

    let albumImage;
    let year = null;

    // ✅ Normalize track_data to always be an object
    const trackData =
        typeof track.track_data === "string"
            ? JSON.parse(track.track_data)
            : track.track_data || {};

    if (track.source === "spotify") {
        albumImage = trackData.album?.images?.[0]?.url ?? albumImage;
        // Extract year from release date
        if (trackData.album?.release_date) {
            year = trackData.album.release_date.substring(0, 4);
        }
    } else if (track.source === "Apple Music") {
        const artwork = trackData.attributes?.artwork;
        if (artwork?.url) {
            albumImage = artwork.url
                .replace("{w}", Math.max(100, artwork.width || 300))
                .replace("{h}", Math.max(100, artwork.height || 300));
        }
        // Extract year from release date
        if (trackData.attributes?.releaseDate) {
            year = trackData.attributes.releaseDate.substring(0, 4);
        }
    }

    return (
        <div
            className={cn(
                "singleCard min-w-[300px] w-full h-full flex",
                layout === "grid" ? "flex-col" : "flex-row",
                rootClass
            )}
        >
            <div>
                {"album" in trackData ? (
                    <img
                        src={albumImage || track?.album_cover}
                        className={`rounded-3xl ${
                            layout === "grid"
                                ? "w-full aspect-square md:aspect-auto md:min-h-[392px]"
                                : "min-w-[120px] max-w-[120px] sm:min-w-[300px] min-h-[300px] h-full"
                        } object-cover`}
                        alt={track.track_name + " image"}
                    />
                ) : (
                    <img
                        src={albumImage || track?.track_data?.album?.images?.[0]?.url || track?.track_data?.attributes?.artwork?.url || track?.album_cover}
                        className={`w-full rounded-3xl ${
                            layout === "grid"
                                ? "w-full aspect-square md:aspect-auto md:min-h-[392px]"
                                : "min-w-[120px] max-w-[120px] sm:min-w-[300px] min-h-[300px] h-full"
                        } object-cover`}
                        alt={track.track_name + " image"}
                    />
                )}
            </div>

            <div
                className={cn(
                    "trackCardMask w-full h-full p-3.5 rounded-[38px] md:rounded-[44px] bottomRightMain relative backdrop-blur-2xl flex flex-col",
                    layout === "grid" ? "-mt-14" : "sm:-ml-8",
                    layout === "grid"
                        ? "bg-[linear-gradient(to_bottom,_#eaeaea50,_#F1F1F1,_#F1F1F1,_#F1F1F1)] dark:bg-[linear-gradient(to_bottom,_#19191982,_#19191982)]"
                        : "bg-[linear-gradient(to_right,_#eaeaea50,_#F1F1F1,_#F1F1F1,_#F1F1F1)] dark:bg-[linear-gradient(to_right,_#19191982,_#19191982)]",
                    theme === "light" &&
                        rtc &&
                        "bg-[linear-gradient(to_bottom,_#eaeaea50,_#FFFFFF,_#FFFFFF,_#FFFFFF)]"
                )}
            >
                {/* Top Bar */}
                <div className="bg-[#ffffffcf] dark:bg-[#FFFFFF1F] rounded-full p-1 pr-1.5 md:p-2 flex justify-between items-center">
                    <p className="flex items-center gap-1 max-w-[calc(100%-110px)]">
                        <span className="dark:invert transform scale-75 md:scale-100 bg-[#E1E0E0] dark:bg-[#E1E0E040] p-2 rounded-full">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                className="lucide lucide-audio-lines w-4 h-4 text-gray-900"
                            >
                                <path d="M2 10v3"></path>
                                <path d="M6 6v11"></path>
                                <path d="M10 3v18"></path>
                                <path d="M14 8v7"></path>
                                <path d="M18 5v13"></path>
                                <path d="M22 10v3"></path>
                            </svg>
                        </span>
                        <MarqueeText text={track.track_name} />
                    </p>
                    <p className="bg-orange-500 text-white px-8 rounded-full py-0.5 text-xs md:text-base">
                        {year || new Date().getFullYear()}
                    </p>
                </div>

                {/* Middle Info */}
                <div className="space-y-1.5 md:space-y-2 py-3.5 md:py-5">
                    <p className="text-base md:text-lg">{track.artist_name}</p>
                    {track.played_at ? (
                        <p className="text-base md:text-lg">
                            {new Date(track.played_at).toLocaleString(undefined, {
                                weekday: "short",
                                month: "short",
                                day: "numeric",
                                hour: "2-digit",
                                minute: "2-digit",
                            })}
                        </p>
                    ) : null}
                    <p className="text-base md:text-lg">
                        Producers:{" "}
                        <span className="text-[#A4A4A4]">
                            {track.producers?.length
                                ? track.producers.map(
                                      (producer, index: number) =>
                                          `${producer.name}${
                                              track.producers.length !==
                                              index + 1
                                                  ? ", "
                                                  : ""
                                          }`
                                  )
                                : "Unknown"}
                        </span>
                    </p>
                </div>

                {/* Genres */}
                <div className="flex items-center gap-1 flex-wrap flex-1">
                    {track.genres?.length ? (
                        track.genres.map((genre) => (
                            <span
                                key={genre.id}
                                className="px-3 py-1 rounded-full text-xs md:text-sm"
                                style={{
                                    backgroundColor: "rgba(234, 97, 21, 0.12)",
                                }}
                            >
                                {genre.name}
                            </span>
                        ))
                    ) : (
                        <span className="px-3 py-1 rounded-full text-xs md:text-sm text-muted-foreground bg-[rgba(234,97,21,0.12)]">
                            No genre
                        </span>
                    )}
                </div>

                {/* Buttons */}
                {(track?.source === "Apple Music" || trackData?.apple_music_url || track?.apple_music_url) && (
                        <button
                            id="appleBtn"
                            className={cn(
                                "bg-gradient-to-r from-[#FA233B] to-[#FB5C74] hover:from-[#FA233B]/90 hover:to-[#FB5C74]/90 text-white rounded-2xl md:rounded-3xl px-2.5 sm:px-3 py-2 md:py-3.5 mt-5",
                                layout === "grid"
                                    ? "2xl:pr-5 max-w-[calc(100%-10rem)]"
                                    : "2xl:pr-5 max-w-[calc(100%-6.3rem)]"
                            )}
                        >
                            <a
                                href={
                                    trackData?.attributes?.url ||
                                    trackData.apple_music_url || track?.apple_music_url ||
                                    "#"
                                }
                                target="_blank"
                                rel="noopener noreferrer"
                                className="w-full whitespace-nowrap flex relative z-[11] items-center gap-2"
                            >
                                <img src="/images/Apple_Music_Icon_wht_lg_072420.svg" alt="Apple Music" className="w-9 h-9 flex-shrink-0" />
                                <span className="truncate flex-1 text-center font-inter text-[13px] sm:text-base">
                                    PLAY ON APPLE MUSIC
                                </span>
                            </a>
                        </button>
                    )}

                {(track?.source === "spotify" ||
                    trackData?.spotify_url || track?.spotify_url) && (
                        <button
                            id="spotifyBtn"
                            className={cn(
                                "bg-[#1DB954] hover:bg-[#1DB954]/90 text-white rounded-2xl md:rounded-3xl px-2.5 sm:px-3 py-2 md:py-3.5 mt-5",
                                layout === "grid"
                                    ? "2xl:pr-5 max-w-[calc(100%-10rem)]"
                                    : "2xl:pr-5 max-w-[calc(100%-6.3rem)]"
                            )}
                        >
                            <a
                                href={
                                    trackData?.attributes?.url ||
                                    trackData?.spotify_url || track?.track_data?.external_urls?.spotify ||
                                    track?.spotify_url ||
                                    "#"
                                }
                                target="_blank"
                                rel="noopener noreferrer"
                                className="w-full whitespace-nowrap flex relative z-[11] items-center gap-2"
                            >
                                <img src="/images/Spotify_logo_white.svg" alt="Spotify" className="w-10 h-10 flex-shrink-0" />
                                <span className="truncate flex-1 text-center font-inter text-[13px] sm:text-base">
                                    PLAY ON SPOTIFY
                                </span>
                            </a>
                        </button>
                    )}
            </div>
        </div>
    );
}
