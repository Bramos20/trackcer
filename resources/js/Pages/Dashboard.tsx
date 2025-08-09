import React, { useState, useRef, useEffect } from "react";
import { Head, router } from "@inertiajs/react";
import { Music, Users, TrendingUp, History, Clock, BarChart3, Disc, Expand, ChartPie, MoveUpRight} from "lucide-react";
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from "recharts";
import { Card, CardHeader, CardTitle, CardContent, CardDescription, CardFooter} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger} from "@/components/ui/tabs";
import {
  ChartConfig,
  ChartContainer,
  ChartTooltip,
} from "@/components/ui/chart";
import AppLayout from "@/Layouts/AppLayout";
import DashboardHeader from "@/components/DashboardHeader";
import StatCard from "@/components/StatCard";
import ListeningHistory from "@/components/ListeningHistory";
import GenreBreakdown from "@/components/GenreBreakdown";
import ProducerGraph from "@/components/ProducerGraph";
import { useTheme } from "next-themes";
import ProducerCard from "@/components/ProducerCard";

export default function Dashboard({
  auth,
  totalTracks,
  totalMinutes,
  totalProducers,
  topProducers,
  history,
  producerNames,
  producerTrackCounts,
  genreNames,
  genreCounts,
  timeframe,
  todayTracks,
  todayProducers,
}) {
  const { theme } = useTheme();
  const mostRecentTrack = history.length ? history[0] : null;

  // Extract album image from track data
  let albumImage = null;
  if (mostRecentTrack) {
    const trackData = typeof mostRecentTrack.track_data === "string"
      ? JSON.parse(mostRecentTrack.track_data)
      : mostRecentTrack.track_data || {};

    if (mostRecentTrack.source === "spotify") {
      albumImage = trackData.album?.images?.[0]?.url || null;
    } else if (mostRecentTrack.source === "Apple Music") {
      const artwork = trackData.attributes?.artwork;
      if (artwork?.url) {
        albumImage = artwork.url
          .replace("{w}", Math.max(100, artwork.width || 300))
          .replace("{h}", Math.max(100, artwork.height || 300));
      }
    }
  }

  const graphRef = useRef();
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [isMobile, setIsMobile] = useState(false);

  // Detect mobile screen
  useEffect(() => {
    const checkIsMobile = () => {
      setIsMobile(window.innerWidth < 640);
    };

    checkIsMobile();
    window.addEventListener('resize', checkIsMobile);

    return () => window.removeEventListener('resize', checkIsMobile);
  }, []);

  // Detect sidebar state from DOM
  useEffect(() => {
    const checkSidebarState = () => {
      const main = document.querySelector('main[data-sidebar-collapsed]');
      if (main) {
        setSidebarCollapsed(main.getAttribute('data-sidebar-collapsed') === 'true');
      }
    };

    // Check initially
    checkSidebarState();

    // Set up observer for changes
    const observer = new MutationObserver(checkSidebarState);
    const main = document.querySelector('main[data-sidebar-collapsed]');
    if (main) {
      observer.observe(main, { attributes: true, attributeFilter: ['data-sidebar-collapsed'] });
    }

    return () => observer.disconnect();
  }, []);

  const handleFullscreen = () => {
    graphRef.current?.requestFullscreen?.();
  };

  // Transform data for recharts
  const chartData = producerNames.map((name, index) => {
    // Find the corresponding producer in topProducers to get the image URL
    const producerData = topProducers.find(p => p.producer?.name === name);
    return {
      name: name,
      tracks: producerTrackCounts[index],
      image_url: producerData?.producer?.image_url || null
    };
  });

  console.log("chartData", chartData);

  const chartConfig = {
    tracks: {
      label: "Tracks Played:  ",
      color: theme === 'dark' ? "#231A55" : "#6A4BFB",
    },
  } satisfies ChartConfig;

  // Get initials for avatar fallback
  const getInitials = (name) => {
    return name
      .split(' ')
      .map(part => part[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };


  return (
    <AppLayout user={auth?.user} enableDataUpdateNotifier={true}>
        <Head
            title="Music Analytics Dashboard"
            style={{ fontSize: '54.63px', fontWeight: '700', lineHeight: '1.2' }}
        />


        <div className="space-y-6 overflow-x-hidden">
        {/* Header section */}
        <DashboardHeader timeframe={timeframe} />

        {/* Stats Cards */}
        <div className={`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-5`}>
          <StatCard
            title="Total Tracks"
            value={totalTracks.toString().slice(0, 2) || "0"}
            valueGray={totalTracks.toString().slice(2) || ""}
            footer="Tracks in your collection"
            badge={todayTracks > 0 ? `+${todayTracks} today` : undefined}
            arrow
          />

          <StatCard
            title="Total Producers"
            value={totalProducers.toString().slice(0, 2) || "0"}
            valueGray={totalProducers.toString().slice(2) || ""}
            footer="Unique Producers in your library"
            badge={todayProducers > 0 ? `+${todayProducers} today` : undefined}
          />

          <StatCard
            title="Most Recent"
            artistDescription={ mostRecentTrack?.artist_name || "N/A"}
            value={
              mostRecentTrack?.track_name || "N/A"
            }
            footer={
              mostRecentTrack
                ? new Date(mostRecentTrack.played_at).toLocaleString(undefined, {
                    month: "short",
                    day: "numeric",
                    hour: "2-digit",
                    minute: "2-digit",
                  })
                : "No recent plays"
            }
            artistName={mostRecentTrack?.album_name || 'NA'}
            albumCover={albumImage}
            isAlbum
          />

          <StatCard
            title="Listening Time"
            value={totalMinutes.toString().slice(0, 2) || "0"}
            valueGray={`${totalMinutes?.toString().slice(2)
                ? `${parseInt(totalMinutes.toString().slice(2))} min`
                : 'min'
              }`}
            footer="Total listening time"
          />
        </div>

        {/* Main Content Tabs */}
        <Tabs defaultValue="producers" className="w-full">
          <div className="relative w-full">
            <div className="overflow-x-auto scrollbar-hide pb-2">
              <TabsList className="mb-4 w-max h-auto p-0.5 bg-white/[0.51] dark:bg-[#191919]/[0.58] rounded-full flex flex-nowrap">
              <TabsTrigger
                value="producers"
                className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
              >
                <BarChart3 className="h-4 w-4" /> Producer Analytics
              </TabsTrigger>
              <TabsTrigger
                value="history"
                className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
              >
                <History className="h-4 w-4" /> Recent History
              </TabsTrigger>
              <TabsTrigger
                value="genres"
                className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
              >
                <ChartPie className="h-4 w-4" /> Genre Breakdown
              </TabsTrigger>
              <TabsTrigger
                value="top-producers"
                className="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full border-0 data-[state=active]:bg-[#6A4BFB] dark:data-[state=active]:bg-white data-[state=active]:text-white dark:data-[state=active]:text-black text-gray-600 dark:text-gray-400 font-medium text-sm sm:text-base transition-all whitespace-nowrap"
              >
                <Users className="h-4 w-4" /> Top Producers
              </TabsTrigger>
            </TabsList>
            </div>
          </div>

          {/* Producers Tab */}
          <TabsContent value="producers" className="space-y-4">
              <Card className="rounded-[31.19px] lg:rounded-[47px] px-3 sm:px-6 py-2 sm:py-4">
              <CardHeader className="px-6 py-3 pb-1 sm:pb-3">
                <div className="flex justify-between items-center">
                  <div>
                      <CardTitle className="text-base sm:text-xl font-medium">Top Producers</CardTitle>
                      <CardDescription className="text-sm sm:text-base">Most played producers in your collection</CardDescription>
                  </div>
                    <Button
                        variant="ghost"
                        className="hidden md:flex bg-white/50 dark:bg-[#191919]/50 hover:bg-white/70 dark:hover:bg-[#191919]/70 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-10 py-3 rounded-xl items-center gap-2 h-auto"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7 17L17 7M17 7H7M17 7V17" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                        <span className="text-base font-normal">View All</span>
                    </Button>
                </div>
              </CardHeader>
              <CardContent className="px-6 pt-0 pb-0">
                <ChartContainer config={chartConfig} className="h-[300px] sm:h-[400px] md:h-[450px] lg:h-[550px] w-full">
                  <BarChart accessibilityLayer data={chartData} margin={{ top: isMobile ? 0 : 5, right: 5, bottom: isMobile ? 0 : -10, left: isMobile ? -25 : -25 }}>
                    <defs>
                      <pattern id="stripes-light" patternUnits="userSpaceOnUse" width="30" height="30">
                        <rect width="30" height="30" fill="#6A4BFB" />
                        <path d="M0,30 L30,0 M-10,10 L10,-10 M20,40 L40,20" stroke="#FFFFFF" strokeWidth="2" />
                      </pattern>
                      <pattern id="stripes-dark" patternUnits="userSpaceOnUse" width="30" height="30">
                        <rect width="30" height="30" fill="#231A55" />
                        <path d="M0,30 L30,0 M-10,10 L10,-10 M20,40 L40,20" stroke="#6A4BFB" strokeWidth="2" />
                      </pattern>
                    </defs>
                    <CartesianGrid vertical={false} />
                    <XAxis
                      dataKey="name"
                      tickLine={false}
                      tickMargin={10}
                      axisLine={false}
                      angle={isMobile ? -45 : 0}
                      textAnchor={isMobile ? "end" : "middle"}
                      height={100}
                      tick={{ fontSize: 13 }}
                      interval={0}
                    />
                    <YAxis
                      tickLine={false}
                      axisLine={false}
                      tickFormatter={(value) => Math.floor(value).toString()}
                      domain={[0, 'dataMax']}
                      tick={{ dx: -10 , fontSize: 13}}
                    />
                    <ChartTooltip
                      cursor={false}
                      content={({ active, payload }) => {
                        if (active && payload && payload.length) {
                          const data = payload[0].payload;
                          return (
                            <div className="bg-[#EA6115]  p-3 rounded-lg shadow-lg border border-[#EA6115]"  style={{ borderRadius: '25.74px' }}>
                              <div className="flex items-center gap-3">
                                {data.image_url && (
                                  <img
                                    src={data.image_url}
                                    alt={data.name}
                                    className="w-10 h-10 rounded-full object-cover"
                                  />
                                )}
                                <div>
                                  <p className="font-semibold text-sm text-white">{data.name}</p>
                                  <p className="text-sm text-gray-100">
                                    Tracks Played: <span className="font-semibold text-[#F6BB9A]">{data.tracks}</span>
                                  </p>
                                </div>
                              </div>
                            </div>
                          );
                        }
                        return null;
                      }}
                    />
                    <Bar dataKey="tracks" fill={theme === 'dark' ? "url(#stripes-dark)" : "url(#stripes-light)"} radius={8} />
                  </BarChart>
                </ChartContainer>
              </CardContent>
            </Card>

              <Card className="rounded-[31.19px] lg:rounded-[47px] px-3 sm:px-6 py-2 sm:py-4">
              <CardHeader className="px-6 pt-3 pb-0">
                <div className="flex justify-between items-center">
                  <div>
                    <CardTitle className="text-base sm:text-xl font-medium">Producer Relationships</CardTitle>
                    <CardDescription className="text-sm sm:text-base">
                      Connections between producers in your library
                    </CardDescription>
                  </div>
                    <Button
                        variant="ghost"
                        className="hidden md:flex bg-white/50 dark:bg-[#191919]/50 hover:bg-white/70 dark:hover:bg-[#191919]/70 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-10 py-3 rounded-xl items-center gap-2 h-auto"
                        onClick={handleFullscreen}
                    >
                        <Expand className="h-4 w-4" />
                        <span className="text-base font-normal">Fullscreen</span>
                    </Button>
                </div>
              </CardHeader>

              <CardContent className="h-[300px] sm:h-[400px] md:h-[450px] lg:h-[500px] px-6 py-0 -mt-2 sm:-mt-1 overflow-hidden">
                <ProducerGraph ref={graphRef} />
              </CardContent>

              <CardFooter className="flex items-center justify-between px-6 py-3">
                <div className="flex items-center space-x-4 text-sm text-muted-foreground">
                  <div className="flex items-center space-x-1">
                    <span className="w-3 h-3 rounded-full" style={{ backgroundColor: '#FF5555' }} /> <span>Producers</span>
                  </div>
                  <div className="flex items-center space-x-1">
                    <span className="w-3 h-3 rounded-full" style={{ backgroundColor: '#EA6115' }} /> <span>Tracks</span>
                  </div>
                  <div className="flex items-center space-x-1">
                    <span className="w-3 h-3 rounded-full" style={{ backgroundColor: '#6A4BFB' }} /> <span>Genres</span>
                  </div>
                </div>
              </CardFooter>
            </Card>
          </TabsContent>

          {/* History Tab */}
          <TabsContent value="history" className="overflow-visible">
            <ListeningHistory history={history} />
          </TabsContent>

          {/* Genres Tab */}
          <TabsContent value="genres">
            <GenreBreakdown genreNames={genreNames} genreCounts={genreCounts} />
          </TabsContent>

          <TabsContent value="top-producers" className="space-y-4">
            { topProducers.length === 0 && (
              <div className="text-center py-10 text-muted-foreground">
                <p>No top producers available.</p>
              </div>
            )}
            <div className="grid grid-cols-1 md:grid-cols-[repeat(auto-fill,minmax(340px,1fr))] gap-6 pb-4">
              {topProducers.length > 0 && topProducers.map((data, index) => (
                // <div key={data.producer.id} className="relative flex-shrink-0 w-full sm:w-[375px]" style={{ height: '630px', aspectRatio: '375/630' }}>
                //     {/* Background SVG */}
                //     <svg
                //         width="375"
                //         height="630"
                //         viewBox="0 0 375 630"
                //         fill="none"
                //         xmlns="http://www.w3.org/2000/svg"
                //         className="absolute inset-0 w-full h-full dark:hidden object-contain"
                //     >
                //         <path d="M9.45573e-05 40.0001L0.00117014 590C0.00122237 612.091 17.9098 630 40.0012 630L237.751 630C253.905 630 267.001 616.904 267.001 600.75C267.001 584.596 280.097 571.5 296.251 571.5L335.002 571.5C357.093 571.5 375.002 553.591 375.002 531.5L375.002 40C375.002 17.9086 357.093 -3.15656e-05 335.002 -3.34968e-05L40.0001 3.49691e-06C17.9087 1.56562e-06 4.23348e-05 17.9087 9.45573e-05 40.0001Z" fill="white" fillOpacity="0.51"/>
                //     </svg>
                //     <svg
                //         width="375"
                //         height="630"
                //         viewBox="0 0 375 630"
                //         fill="none"
                //         xmlns="http://www.w3.org/2000/svg"
                //         className="absolute inset-0 w-full h-full hidden dark:block object-contain"
                //     >
                //         <path d="M9.45573e-05 40.0001L0.00117014 590C0.00122237 612.091 17.9098 630 40.0012 630L237.751 630C253.905 630 267.001 616.904 267.001 600.75C267.001 584.596 280.097 571.5 296.251 571.5L335.002 571.5C357.093 571.5 375.002 553.591 375.002 531.5L375.002 40C375.002 17.9086 357.093 -3.15656e-05 335.002 -3.34968e-05L40.0001 3.49691e-06C17.9087 1.56562e-06 4.23348e-05 17.9087 9.45573e-05 40.0001Z" fill="#191919" fillOpacity="0.51"/>
                //     </svg>

                //     {/* Card Content */}
                //     <div className="relative z-10 p-5 h-full flex flex-col">

                //         {/* Content wrapper that grows */}
                //         <div className="flex-1">
                //             {/* Header with image and name */}
                //             <div className="flex items-center gap-4 mb-6">
                //                 {data.producer.image_url ? (
                //                     <img
                //                         src={data.producer.image_url}
                //                         alt={data.producer.name}
                //                         className="w-20 h-20 rounded-full object-cover border-2 border-white/20"
                //                     />
                //                 ) : (
                //                     <div className="w-20 h-20 rounded-full bg-gray-300 dark:bg-gray-700 border-2 border-white/20" />
                //                 )}
                //                 <div className="flex-1">
                //                     <h3 className="text-2xl font-bold break-words">{data.producer.name}</h3>
                //                     <p className="text-gray-600 dark:text-gray-400">{data.producer.role || "Producer"}</p>
                //                 </div>
                //             </div>

                //             {/* Total Stats */}
                //             <div className="mb-3">
                //                 <h4 className="text-base font-semibold mb-3">Total Stats:</h4>
                //                 <div className="flex items-center gap-4 p-3 rounded-lg bg-white/[0.64] dark:bg-white/[0.12]">
                //                     <div className="flex-1 pl-2">
                //                         <p className="text-lg font-normal">{data.track_count} <span className="text-lg font-normal text-gray-600 dark:text-gray-400">Tracks</span></p>
                //                     </div>
                //                     <div className="w-px h-10 bg-gray-300 dark:bg-gray-600"></div>
                //                     <div className="flex-1 pl-2">
                //                         <p className="text-lg font-normal">{data.total_minutes} <span className="text-lg font-normal text-gray-600 dark:text-gray-400">Minutes</span></p>
                //                     </div>
                //                 </div>
                //             </div>

                //             {/* Average Popularity */}
                //             <div className="mb-3">
                //                 <h4 className="text-base font-semibold mb-2">Average Popularity</h4>
                //                 <div className="relative w-full h-8 bg-white dark:bg-white/10 rounded-[10px] overflow-hidden">
                //                     <div
                //                         className="absolute inset-y-0 left-0 rounded-[10px]"
                //                         style={{
                //                             width: `${data.average_popularity}%`,
                //                             backgroundColor: '#EA6115',
                //                             backgroundImage: 'repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0,0,0,.1) 10px, rgba(0,0,0,.1) 20px)'
                //                         }}
                //                     >
                //                         <span className="absolute right-2 top-1/2 -translate-y-1/2 text-white font-normal text-sm">{data.average_popularity}%</span>
                //                     </div>
                //                 </div>
                //             </div>

                //             {/* Latest Track */}
                //             <div className="mb-3">
                //                 <h4 className="text-base font-semibold mb-2">Latest Track</h4>
                //                 {data.latest_track ? (
                //                     <>
                //                         <p className="text-gray-600 dark:text-gray-400">{data.latest_track.track_name}</p>
                //                         <p className="text-gray-500 dark:text-gray-500 text-sm">{data.latest_track.artist_name}</p>
                //                     </>
                //                 ) : (
                //                     <p className="text-gray-400 dark:text-gray-500">No recent track available</p>
                //                 )}
                //             </div>

                //             {/* Genres */}
                //             <div className="mb-3">
                //                 <h4 className="text-base font-semibold mb-2">Genres</h4>
                //                 <div className="flex flex-wrap gap-2 max-h-[75px] md:max-h-[87px] overflow-y-auto">
                //                     {data.genres?.length ? (
                //                         <>
                //                             {data.genres.slice(0, 8).map((genre: string, i: number) => (
                //                                 <span
                //                                     key={i}
                //                                     className="px-3 py-1 rounded-full text-sm"
                //                                     style={{ backgroundColor: 'rgba(234, 97, 21, 0.12)' }}
                //                                 >
                //                                     {genre}
                //                                 </span>
                //                             ))}
                //                         </>
                //                     ) : (
                //                         <p className="text-gray-400 dark:text-gray-500 text-sm">No genres available</p>
                //                     )}
                //                 </div>
                //             </div>
                //         </div>

                //         {/* Buttons */}
                //         <div className="mt-auto space-y-2">
                //             {/* Create Playlist Button */}
                //             <div className="flex justify-end">
                //                 <Button
                //                     onClick={() => router.post(route('producers.createPlaylist', data.producer.id))}
                //                     className="px-6 text-white text-sm font-normal"
                //                     style={{ backgroundColor: '#EA6115', height: '22px', borderRadius: '20px'}}
                //                 >
                //                     Create Playlist
                //                 </Button>
                //             </div>

                //             {/* View Producer Button */}
                //             <div>
                //                 <Button
                //                     onClick={() => router.visit(route("producer.show", data.producer.id))}
                //                     className="text-white font-medium text-sm tracking-wide flex items-center justify-center gap-2"
                //                     style={{ backgroundColor: '#6A4BFB', width: '230px', height: '55px', borderRadius: '22px' }}
                //                 >
                //                     <MoveUpRight className="w-4 h-4" />
                //                     VIEW PRODUCER
                //                 </Button>
                //             </div>
                //         </div>
                //     </div>
                // </div>
                <ProducerCard key={data?.producer?.id} producer={data}/>
              ))}
            </div>

            {/* Load More Button */}
            {
              topProducers.length > 4 && (
                <div className="flex justify-end sm:justify-end items-center mt-6 sm:mt-10 relative z-10">
                  <Button
                    onClick={() => router.visit('/producers')}
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
              )
            }
          </TabsContent>
        </Tabs>
      </div>
    </AppLayout>
  );
}
