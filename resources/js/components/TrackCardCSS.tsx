import React from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Link } from "@inertiajs/react";
import { useTheme } from "next-themes";
import { AudioLines } from "lucide-react";
import MarqueeText from "@/components/MarqueeText";

export default function TrackCardCSS({ track, playedAt, viewMode = "list", variant = "default", index = 0 }) {
  const { theme } = useTheme();

  // Debug logging
  if (variant === "producer-page" || variant === "artist-page") {
    console.log("TrackCard debug:", {
      variant,
      track_name: track.track_name,
      play_count: track.play_count,
      played_at: track.played_at
    });
  }

  const trackData =
    typeof track.track_data === "string"
      ? JSON.parse(track.track_data)
      : track.track_data || {};

  let albumImage = "https://via.placeholder.com/85";
  let trackUrl = null;
  let duration = null;
  let year = null;

  if (track.source === "spotify") {
    albumImage = trackData.album?.images?.[0]?.url ?? albumImage;
    trackUrl = trackData?.external_urls?.spotify;
    // Extract duration in ms and convert to mm:ss format
    if (trackData.duration_ms) {
      const minutes = Math.floor(trackData.duration_ms / 60000);
      const seconds = Math.floor((trackData.duration_ms % 60000) / 1000);
      duration = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }
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
    trackUrl = trackData?.attributes?.url;
    // Extract duration in ms and convert to mm:ss format
    if (trackData.attributes?.durationInMillis) {
      const minutes = Math.floor(trackData.attributes.durationInMillis / 60000);
      const seconds = Math.floor((trackData.attributes.durationInMillis % 60000) / 1000);
      duration = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }
    // Extract year from release date
    if (trackData.attributes?.releaseDate) {
      year = trackData.attributes.releaseDate.substring(0, 4);
    }
  }

  if (viewMode === "grid") {
    return (
      <>
        {/* Desktop version - hidden on mobile */}
        <div
          className={`hidden sm:block relative mx-auto w-full max-w-[355px] track-card-css mb-12 ${
            index >= 1 ? '-mt-12' : ''
          } ${
            index >= 2 ? 'sm:-mt-12' : 'sm:mt-0'
          } ${
            index >= 3 ? 'lg:-mt-12' : 'lg:mt-0'
          } ${
            index >= 4 ? '2xl:-mt-12' : '2xl:mt-0'
          }`}
        >
          <div className={`track-card-container`}>
            <div className={`track-card-inner ${theme === 'dark' ? 'track-card-dark' : 'track-card-light'}`}>
              <div className="relative z-10 flex flex-col">
              {/* Album cover with rounded corners */}
              <div className="relative overflow-hidden rounded-t-[16px]" style={{ paddingBottom: '115%' }}>
                <img
                  src={albumImage}
                  alt={track.album_name}
                  className="absolute inset-0 w-full h-full object-cover"
                />

                {/* Overlay positioned lower on the image */}
                <div className="album-overlay">
                  {/* Song title positioned at the bottom of overlay */}
                  <div className="absolute bottom-1 left-4 right-4 z-20">
                    <div
                      className="flex items-center gap-3 px-4 py-2"
                      style={{
                        backgroundColor: theme === 'dark' ? 'rgba(255, 255, 255, 0.12)' : 'rgba(255, 255, 255, 0.81)',
                        borderRadius: '35.82px'
                      }}
                    >
                      <div className={`w-6 h-6 rounded-full ${theme === 'dark' ? 'bg-white/20' : 'bg-black/10'} backdrop-blur-sm flex items-center justify-center flex-shrink-0`}>
                        <AudioLines className={`w-4 h-4 ${theme === 'dark' ? 'text-white' : 'text-gray-900'}`} />
                      </div>
                      <MarqueeText
                        text={track.track_name}
                        className={`text-base font-medium ${theme === 'dark' ? 'text-white' : 'text-gray-900'} flex-1`}
                      />
                      <span
                        className="flex items-center justify-center text-xs font-medium text-white w-[62px] h-[20px]"
                        style={{
                          backgroundColor: '#EA6115',
                          borderRadius: '40px'
                        }}
                      >
                        {year || new Date().getFullYear()}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div className="px-4 py-4 flex flex-col relative">
                <div>
                  <h3 className="text-sm font-bold mb-2">
                    <MarqueeText text={track.artist_name} />
                  </h3>

                  <div className="text-sm text-muted-foreground mb-3">
                    <MarqueeText text={track.album_name} />
                  </div>

                  {(variant === "producer-page" || variant === "artist-page") && track.play_count ? (
                    <p className="text-sm text-muted-foreground mb-4">
                      Played {track.play_count} {track.play_count === 1 ? 'time' : 'times'}
                    </p>
                  ) : track.played_at ? (
                    <p className="text-sm text-muted-foreground mb-4">
                      {new Date(track.played_at).toLocaleString(undefined, {
                        weekday: "short",
                        month: "short",
                        day: "numeric",
                        hour: "2-digit",
                        minute: "2-digit",
                      })}
                    </p>
                  ) : null}

                  {variant !== "producer-page" && (
                    <p className="text-sm mb-5">
                      <span className="text-muted-foreground">Producers:</span> <span className="text-foreground">{track.producers.length > 0
                        ? track.producers.slice(0, 2).map(p => p.name).join(", ")
                        : "Unknown"}</span>
                    </p>
                  )}

                  <div className="flex flex-wrap gap-2">
                    {track.genres && track.genres.length > 0 ? (
                      track.genres.slice(0, 4).map((genre) => (
                        <span
                          key={genre.id}
                          className="px-3 py-1.5 rounded-lg text-xs h-fit"
                          style={{ backgroundColor: 'rgba(234, 97, 21, 0.12)' }}
                        >
                          {genre.name}
                        </span>
                      ))
                    ) : (
                      <span
                        className="px-3 py-1.5 rounded-lg text-xs text-muted-foreground h-fit"
                        style={{ backgroundColor: 'rgba(234, 97, 21, 0.12)' }}
                      >
                        No genre
                      </span>
                    )}
                  </div>
                </div>

                <div className="absolute" style={{ bottom: '10px', left: '16px', zIndex: 40 }}>
                  {trackUrl && (
                    track.source === "spotify" ? (
                      <Button
                        asChild
                        className="bg-[#1DB954] hover:bg-[#1DB954]/90 text-white font-medium text-sm tracking-wide"
                        style={{ width: '220px', height: '55px', borderRadius: '22px' }}
                      >
                        <a href={trackUrl} target="_blank" rel="noopener noreferrer" className="flex items-center justify-start gap-3 pl-2">
                          <img src="/images/Spotify_logo_white.svg" alt="Spotify" className="w-10 h-10" />
                          PLAY WITH SPOTIFY
                        </a>
                      </Button>
                    ) : (
                      <Button
                        asChild
                        className="bg-gradient-to-r from-[#FA233B] to-[#FB5C74] hover:from-[#FA233B]/90 hover:to-[#FB5C74]/90 text-white font-medium text-sm tracking-wide w-full max-w-[220px]"
                        style={{ height: '55px', borderRadius: '22px' }}
                      >
                        <a href={trackUrl} target="_blank" rel="noopener noreferrer" className="flex items-center justify-start gap-2 pl-3">
                          <img src="/images/Apple_Music_Icon_wht_lg_072420.svg" alt="Apple Music" className="w-9 h-9" />
                          PLAY ON APPLE MUSIC
                        </a>
                      </Button>
                    )
                  )}
                </div>
              </div>
              </div>
            </div>
            <div className="track-card-bottom">
              <div className={`track-card-bottom-left ${theme === 'dark' ? 'track-card-dark' : 'track-card-light'}`}></div>
              <div className="track-card-bottom-cutout"></div>
              <div className={`track-card-bottom-right ${theme === 'dark' ? 'track-card-dark' : 'track-card-light'}`}></div>
            </div>
          </div>
        </div>

        {/* Mobile version - visible only on mobile */}
        <div
          className={`sm:hidden relative mx-auto w-full max-w-[355px] track-card-css mb-12 ${
            index >= 1 ? '-mt-16' : ''
          }`}
        >
          <div className={`track-card-container`}>
            <div className={`track-card-inner ${theme === 'dark' ? 'track-card-dark' : 'track-card-light'}`}>
              <div className="relative z-10 flex flex-col">
              {/* Album cover with rounded corners */}
              <div className="relative overflow-hidden rounded-t-[16px]" style={{ paddingBottom: '100%' }}>
                <img
                  src={albumImage}
                  alt={track.album_name}
                  className="absolute inset-0 w-full h-full object-cover"
                />

                {/* Overlay positioned lower on the image */}
                <div className="album-overlay">
                  {/* Song title positioned at the bottom of overlay */}
                  <div className="absolute bottom-1 left-4 right-4 z-20">
                    <div
                      className="flex items-center gap-3 px-4 py-2"
                      style={{
                        backgroundColor: theme === 'dark' ? 'rgba(255, 255, 255, 0.12)' : 'rgba(255, 255, 255, 0.81)',
                        borderRadius: '35.82px'
                      }}
                    >
                      <div className={`w-6 h-6 rounded-full ${theme === 'dark' ? 'bg-white/20' : 'bg-black/10'} backdrop-blur-sm flex items-center justify-center flex-shrink-0`}>
                        <AudioLines className={`w-4 h-4 ${theme === 'dark' ? 'text-white' : 'text-gray-900'}`} />
                      </div>
                      <MarqueeText
                        text={track.track_name}
                        className={`text-base font-medium ${theme === 'dark' ? 'text-white' : 'text-gray-900'} flex-1`}
                      />
                      <span
                        className="flex items-center justify-center text-xs font-medium text-white w-[62px] h-[20px]"
                        style={{
                          backgroundColor: '#EA6115',
                          borderRadius: '40px'
                        }}
                      >
                        {year || new Date().getFullYear()}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div className="px-4 py-4 flex flex-col relative">
                <div>
                  <h3 className="text-sm font-bold mb-2">
                    <MarqueeText text={track.artist_name} />
                  </h3>

                  <div className="text-sm text-muted-foreground mb-3">
                    <MarqueeText text={track.album_name} />
                  </div>

                  {(variant === "producer-page" || variant === "artist-page") && track.play_count ? (
                    <p className="text-sm text-muted-foreground mb-4">
                      Played {track.play_count} {track.play_count === 1 ? 'time' : 'times'}
                    </p>
                  ) : track.played_at ? (
                    <p className="text-sm text-muted-foreground mb-4">
                      {new Date(track.played_at).toLocaleString(undefined, {
                        weekday: "short",
                        month: "short",
                        day: "numeric",
                        hour: "2-digit",
                        minute: "2-digit",
                      })}
                    </p>
                  ) : null}

                  {variant !== "producer-page" && (
                    <p className="text-sm mb-5">
                      <span className="text-muted-foreground">Producers:</span> <span className="text-foreground">{track.producers.length > 0
                        ? track.producers.slice(0, 2).map(p => p.name).join(", ")
                        : "Unknown"}</span>
                    </p>
                  )}

                  <div className="flex flex-wrap gap-2 mt-2">
                    {track.genres && track.genres.length > 0 ? (
                      track.genres.slice(0, 4).map((genre) => (
                        <span
                          key={genre.id}
                          className="px-3 py-1.5 rounded-lg text-xs h-fit"
                          style={{ backgroundColor: 'rgba(234, 97, 21, 0.12)' }}
                        >
                          {genre.name}
                        </span>
                      ))
                    ) : (
                      <span
                        className="px-3 py-1.5 rounded-lg text-xs text-muted-foreground h-fit"
                        style={{ backgroundColor: 'rgba(234, 97, 21, 0.12)' }}
                      >
                        No genre
                      </span>
                    )}
                  </div>
                </div>

                <div className="absolute" style={{ bottom: '10px', left: '16px', zIndex: 40 }}>
                  {trackUrl && (
                    track.source === "spotify" ? (
                      <Button
                        asChild
                        className="bg-[#1DB954] hover:bg-[#1DB954]/90 text-white font-medium text-sm tracking-wide"
                        style={{ width: '220px', height: '55px', borderRadius: '22px' }}
                      >
                        <a href={trackUrl} target="_blank" rel="noopener noreferrer" className="flex items-center justify-start gap-3 pl-2">
                          <img src="/images/Spotify_logo_white.svg" alt="Spotify" className="w-10 h-10" />
                          PLAY WITH SPOTIFY
                        </a>
                      </Button>
                    ) : (
                      <Button
                        asChild
                        className="bg-gradient-to-r from-[#FA233B] to-[#FB5C74] hover:from-[#FA233B]/90 hover:to-[#FB5C74]/90 text-white font-medium text-sm tracking-wide"
                        style={{ width: '220px', height: '55px', borderRadius: '22px' }}
                      >
                        <a href={trackUrl} target="_blank" rel="noopener noreferrer" className="flex items-center justify-start gap-2 pl-3">
                          <img src="/images/Apple_Music_Icon_wht_lg_072420.svg" alt="Apple Music" className="w-9 h-9" />
                          PLAY ON APPLE MUSIC
                        </a>
                      </Button>
                    )
                  )}
                </div>
              </div>
              </div>
            </div>
            <div className="track-card-bottom">
              <div className={`track-card-bottom-left ${theme === 'dark' ? 'track-card-dark' : 'track-card-light'}`}></div>
              <div className="track-card-bottom-cutout"></div>
              <div className={`track-card-bottom-right ${theme === 'dark' ? 'track-card-dark' : 'track-card-light'}`}></div>
            </div>
          </div>
        </div>

        <style jsx>{`
          .track-card-css {
            container-type: inline-size;
            --card-bg-light: #ebebeb;
            --card-bg-dark: #271f4b;
          }

          .track-card-container {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: visible;
            filter: drop-shadow(0 10px 40px rgba(0, 0, 0, 0.1));
          }
          
          /* Inner container with the cutout shape */
          .track-card-inner {
            width: 100%;
            height: calc(100% - 59px);
            position: relative;
            border-radius: 35px 35px 0 0;
            overflow: hidden;
          }
          
          /* Bottom section with cutout */
          .track-card-bottom {
            position: absolute;
            bottom: -59px;
            left: 0;
            right: 0;
            height: 59px;
            display: flex;
          }
          
          .track-card-bottom-left {
            width: 61%;
            height: 100%;
            border-radius: 0 0 0 35px;
          }
          
          .track-card-bottom-cutout {
            width: 17%;
            height: 100%;
            position: relative;
            overflow: hidden;
          }
          
          .track-card-bottom-cutout::before {
            content: '';
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #6B46C1;
          }
          
          .track-card-bottom-right {
            flex: 1;
            height: 100%;
            border-radius: 0 0 35px 0;
          }

          .track-card-light {
            background: #ebebeb;
          }

          .track-card-dark {
            background: #271f4b;
          }

          /* Curved overlay */
          .album-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 55px;
            z-index: 10;
          }

          .album-overlay::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100%;
            border-radius: 35px 35px 0 0;
          }

          .track-card-light .album-overlay::before {
            background: linear-gradient(to bottom, rgba(235, 235, 235, 0.9), rgba(235, 235, 235, 1));
          }

          .track-card-dark .album-overlay::before {
            background: linear-gradient(to bottom, rgba(39, 31, 75, 0.9), rgba(39, 31, 75, 1));
          }

          /* Add subtle inner shadow for depth */
          .track-card-container::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 35px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1);
            pointer-events: none;
          }

          /* Optional: Add a subtle gradient overlay for more depth */
          .track-card-dark::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
            border-radius: 35px;
          }
        `}</style>
      </>
    );
  }

  // List view (existing layout)
  const listBgClass = ["dashboard", "artist-page"].includes(variant)
  ? "bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700"
  : "bg-card border";

  return (
    <div className={`flex items-start gap-3 sm:gap-4 p-4 sm:p-6 rounded-lg ${listBgClass}`}>
      <img
        src={albumImage}
        alt="Album Cover"
        className="w-20 h-20 sm:w-[120px] sm:h-[120px] object-cover rounded-lg flex-shrink-0"
      />
      <div className="flex-1">
        <h4 className="text-base sm:text-xl font-bold mb-1">
        <MarqueeText text={track.track_name} />
      </h4>

        <div className="text-sm text-muted-foreground">
          <MarqueeText text={track.artist_name} />
        </div>

        <div className="text-xs sm:text-sm text-muted-foreground">
        Album: <MarqueeText text={track.album_name} className="inline-block ml-1" />
      </div>

        <div className="flex items-center gap-4 mt-1">
          <p className="text-xs text-muted-foreground">
            {duration || "--:--"}
          </p>
          <p className="text-xs text-muted-foreground">
            {year || "----"}
          </p>
        </div>

        {(variant === "producer-page" || variant === "artist-page") && track.play_count ? (
          <p className="text-xs text-muted-foreground mt-1">
            <svg className="w-3 h-3 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Played {track.play_count} {track.play_count === 1 ? 'time' : 'times'}
          </p>
        ) : track.played_at ? (
          <p className="text-xs text-muted-foreground mt-1">
            <svg className="w-3 h-3 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {new Date(track.played_at).toLocaleString(undefined, {
              weekday: "short",
              month: "short",
              day: "numeric",
              hour: "2-digit",
              minute: "2-digit",
            })}
          </p>
        ) : null}

        <div className="mt-2">
          {track.genres && track.genres.length > 0 ? (
            <div className="flex flex-wrap gap-1">
              {track.genres.map((genre) => (
                <span key={genre.id} className="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs bg-muted/50 dark:bg-muted/20 border border-border/50">
                  {genre.name}
                </span>
              ))}
            </div>
          ) : (
            <span className="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs bg-muted/50 dark:bg-muted/20 border border-border/50 text-muted-foreground">
              No genre
            </span>
          )}
        </div>

        {variant !== "producer-page" && (
          <p className="text-sm text-muted-foreground mt-2">
            Producers:{" "}
            {track.producers.length > 0
              ? track.producers.map((producer, i) => (
                  <span key={producer.id}>
                    <Link
                      href={route("producer.tracks", producer.id)}
                      className="text-foreground hover:underline"
                    >
                      {producer.name}
                    </Link>
                    {i < track.producers.length - 1 ? ", " : ""}
                  </span>
                ))
              : "Unknown"}
          </p>
        )}

        <div className="mt-4 flex items-center gap-3">
          {trackUrl && (
            <div>
              {track.source === "spotify" ? (
                <Button
                  asChild
                  className={`inline-flex items-center justify-center rounded-lg bg-[#1DB954] hover:bg-[#1DB954]/90 text-white font-medium transition-colors ${
                    ["dashboard", "artist-page"].includes(variant)
                      ? "gap-1 px-2 py-1 text-xs sm:gap-2 sm:px-4 sm:py-2 sm:text-sm"
                      : "gap-2 px-4 py-2 text-sm"
                  }`}
                >
                  <a href={trackUrl} target="_blank" rel="noopener noreferrer">
                    <img
                      src="https://upload.wikimedia.org/wikipedia/commons/8/84/Spotify_icon.svg"
                      alt="Spotify"
                      className={variant === "dashboard" ? "w-4 h-4 sm:w-5 sm:h-5" : "w-5 h-5"}
                    />
                    Play on Spotify
                  </a>
                </Button>
              ) : (
                <Button
                  asChild
                  className={`inline-flex items-center justify-center rounded-lg bg-[#FA233B] hover:bg-[#FA233B]/90 text-white font-medium transition-colors ${
                    ["dashboard", "artist-page"].includes(variant)
                      ? "gap-1 px-2 py-1 text-xs sm:gap-2 sm:px-4 sm:py-2 sm:text-sm"
                      : "gap-2 px-4 py-2 text-sm"
                  }`}
                >
                  <a href={trackUrl} target="_blank" rel="noopener noreferrer">
                    <img
                      src="/images/Apple_Music_icon.svg"
                      alt="Apple Music"
                      className={variant === "dashboard" ? "w-4 h-4 sm:w-5 sm:h-5" : "w-5 h-5"}
                    />
                    Play on Apple Music
                  </a>
                </Button>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}