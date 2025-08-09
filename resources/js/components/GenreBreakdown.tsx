import React from "react";
import { Pie, PieChart, Cell, ResponsiveContainer } from "recharts";
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
    CardFooter,
} from "@/components/ui/card";
import {
    ChartConfig,
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from "@/components/ui/chart";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

export default function GenreBreakdown({ genreNames = [], genreCounts = [] }) {
    const maxCount = Math.max(...genreCounts);

    // Define chart colors - fallback colors matching the theme
    const chartColors = [
        "#EA6115", // Orange (matching badge)
        "#261A59", // Purple
        "#4531A4", // Dark purple
        "#6A4BFB", // Blue
        "#F19436", // Red
    ];

    // Transform data for recharts pie chart
    const chartData = genreNames.map((name, index) => ({
        genre: name,
        count: genreCounts[index] || 0,
        fill: chartColors[index % chartColors.length],
    }));

    // Create chart config
    const chartConfig = {
        count: {
            label: "Tracks",
        },
        ...genreNames.reduce((acc, name, index) => {
            acc[name] = {
                label: name,
                color: chartColors[index % chartColors.length],
            };
            return acc;
        }, {} as Record<string, { label: string; color: string }>),
    } satisfies ChartConfig;

    const renderGenreList = (start, end) =>
        genreNames.slice(start, end).map((name, i) => {
            const index = start + i;
            const count = genreCounts[index] || 0;
            const percent = ((count / maxCount) * 100).toFixed(2);
            const color = chartColors[index % chartColors.length];

            return (
                <div key={index} className="mb-4">
                    <div className="flex justify-between items-center mb-1">
                        <span className="text-sm sm:text-base font-medium">{name}</span>
                    </div>
                    <div className="relative w-full h-[17.87px] sm:h-[31px]">
                        {/* Background track */}
                        <div className="absolute inset-0 bg-[#AEAEAE]/10 dark:bg-white/10 rounded-[5.93px] sm:rounded-[10px]" />
                        {/* Filled bar */}
                        <div
                            className="absolute top-0 left-0 h-full rounded-[5.93px] sm:rounded-[10px] flex items-center justify-end pr-2 sm:pr-3"
                            style={{
                                width: `${Math.max(parseFloat(percent), 20)}%`,
                                backgroundColor: color,
                                backgroundImage: `repeating-linear-gradient(
                                    20deg,
                                    transparent,
                                    transparent 8px,
                                    rgba(255, 255, 255, 0.15) 8px,
                                    rgba(255, 255, 255, 0.15) 12px
                                )`
                            }}
                        >
                            <span className="text-white text-xs sm:text-sm font-medium">
                                {count} tracks
                            </span>
                        </div>
                    </div>
                </div>
            );
        });

    return (
        <Card className="rounded-[31.19px] lg:rounded-[47px] pl-3 sm:pl-6 pr-0 sm:pr-2 py-2 sm:py-4">
            <CardHeader className="pl-6 pr-6 py-3 pb-0 sm:pb-0">
                <div>
                    <CardTitle className="text-base sm:text-xl font-medium">Genre Distribution</CardTitle>
                    <CardDescription className="text-sm sm:text-base">Your most listened to music genres</CardDescription>
                </div>
            </CardHeader>

            <CardContent className="pl-0 pr-0 py-0">
                {chartData.length === 0 && <div className="text-center py-10 text-muted-foreground">
                    <p>No genre data available.</p>
                </div>}
                <div className="flex flex-col md:grid md:grid-cols-12 gap-4">
                    {/* Genre list on the left - takes 7 columns - order-2 on mobile */}
                    <div className="order-2 md:order-1 pl-6 pr-6 md:pr-0 md:col-span-7">
                        <div className="space-y-3">{renderGenreList(0, genreNames.length)}</div>
                    </div>

                    {/* Donut chart on the right - takes 5 columns - order-1 on mobile */}
                    <div className="order-1 md:order-2 flex justify-center md:justify-end items-center relative md:-mr-4 md:col-span-5">
                        {chartData.length > 0 && (
                            <>
                                <ChartContainer
                                    config={chartConfig}
                                    className="mx-auto md:mx-0 aspect-square h-[300px] sm:h-[350px] md:h-[420px] w-full"
                                >
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <ChartTooltip
                                                cursor={false}
                                                content={<ChartTooltipContent hideLabel />}
                                            />
                                            <Pie
                                                data={chartData}
                                                dataKey="count"
                                                nameKey="genre"
                                                innerRadius="53%"
                                                outerRadius="85%"
                                                paddingAngle={1.2}
                                                cornerRadius={4}
                                            >
                                                {chartData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={entry.fill} />
                                                ))}
                                            </Pie>
                                        </PieChart>
                                    </ResponsiveContainer>
                                </ChartContainer>
                                {/* Badge in center */}
                                <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <Badge
                                        variant="secondary"
                                        className="bg-[#EA6115] text-white border-0 text-xs sm:text-sm font-medium flex items-center justify-center w-[85px] sm:w-[105px] h-[25px] sm:h-[32px]"
                                        style={{
                                            borderRadius: '81.97px'
                                        }}
                                    >
                                        {genreNames.length} Genres
                                    </Badge>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </CardContent>

            <CardFooter className="flex justify-right pl-6 pr-6 py-1">
                {chartData.length > 0 ? (
                    <Button className="bg-[#6A4BFB] text-white rounded-[14.6px] md:rounded-[22px] hover:bg-[#5A3BEB] w-[155px] h-[40px] md:w-[220px] md:h-[55px] text-xs md:text-sm">
                    VIEW ALL GENRES
                    </Button>) : ""}
                
            </CardFooter>
        </Card>
    );
}
