import React, { useMemo } from "react";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from "@/components/ui/card";
import {
    XAxis,
    YAxis,
    PieChart,
    Pie,
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
} from "recharts";
import {
    ChartConfig,
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from "@/components/ui/chart";

// Custom tooltip component for weekly listening duration
const CustomTooltip = ({ active, payload, label }: any) => {
    if (active && payload && payload.length) {
        return (
            <div
                className="px-4 py-3 shadow-lg"
                style={{
                    backgroundColor: "#EA6115",
                    borderRadius: "25.74px",
                }}
            >
                {/* <p className="font-semibold text-sm text-white">{label}</p> */}
                <p className="text-sm text-gray-100">
                    Minutes:{" "}
                    <span className="font-semibold text-[#F6BB9A]">
                        {payload[0].value}
                    </span>
                </p>
            </div>
        );
    }
    return null;
};

export default function StatsTab({
    durationByGenre,
    popularityDistribution,
    weeklyListeningData,
    collaborationBreakdown,
}) {
    // Convert object to array for recharts and sort by duration
    const durationData = useMemo(() => {
        const data = [];
        for (const genre in durationByGenre || {}) {
            if ((durationByGenre || {}).hasOwnProperty(genre)) {
                data.push({
                    genre,
                    duration: durationByGenre[genre] as number,
                });
            }
        }
        // Sort by duration in descending order
        return data.sort((a, b) => b.duration - a.duration);
    }, [durationByGenre]);

    const collaborationData = useMemo(() => {
        const data = [];
        for (const label in collaborationBreakdown || {}) {
            if ((collaborationBreakdown || {}).hasOwnProperty(label)) {
                data.push({
                    label,
                    value: collaborationBreakdown[label] as number,
                });
            }
        }
        return data;
    }, [collaborationBreakdown]);

    // Group popularity scores into buckets
    const popularityBuckets = useMemo(() => {
        const ranges = {
            "0-20": 0,
            "21-40": 0,
            "41-60": 0,
            "61-80": 0,
            "81-100": 0,
        };

        (popularityDistribution || []).forEach((score: number) => {
            if (score <= 20) ranges["0-20"]++;
            else if (score <= 40) ranges["21-40"]++;
            else if (score <= 60) ranges["41-60"]++;
            else if (score <= 80) ranges["61-80"]++;
            else ranges["81-100"]++;
        });

        const chartColors = [
            "var(--color-chart-1)",
            "var(--color-chart-2)",
            "var(--color-chart-3)",
            "var(--color-chart-4)",
            "var(--color-chart-5)",
        ];

        const data = [];
        let index = 0;
        for (const range in ranges) {
            if (ranges.hasOwnProperty(range)) {
                data.push({
                    range,
                    count: ranges[range],
                    fill: chartColors[index],
                });
                index++;
            }
        }
        return data;
    }, [popularityDistribution]);

    // Chart configurations

    const popularityChartConfig = {
        count: {
            label: "Tracks",
        },
        "0-20": {
            label: "Low (0-20)",
            color: "var(--color-chart-1)",
        },
        "21-40": {
            label: "Below Average (21-40)",
            color: "var(--color-chart-2)",
        },
        "41-60": {
            label: "Average (41-60)",
            color: "var(--color-chart-3)",
        },
        "61-80": {
            label: "Above Average (61-80)",
            color: "var(--color-chart-4)",
        },
        "81-100": {
            label: "High (81-100)",
            color: "var(--color-chart-5)",
        },
    } satisfies ChartConfig;

    const weeklyChartConfig = {
        duration: {
            label: "Minutes",
            color: "var(--chart-1)",
        },
    } satisfies ChartConfig;

    const collabColors = [
        "var(--color-chart-1)",
        "var(--color-chart-2)",
        "var(--color-chart-3)",
        "var(--color-chart-4)",
        "var(--color-chart-5)",
    ];

    const collaborationChartConfig = collaborationData.reduce(
        (
            acc: ChartConfig,
            item: { label: string; value: number },
            index: number
        ) => {
            acc[item.label] = {
                label: item.label,
                color: collabColors[index % collabColors.length],
            };
            return acc;
        },
        {
            value: {
                label: "Count",
            },
        } as ChartConfig
    );

    return (
        <Card className="rounded-[31.19px] lg:rounded-[47px] px-2 sm:px-6 py-2 sm:py-4 dark:border-none">
            <CardHeader className="px-3 py-3 pb-1 sm:pb-3">
                <div>
                    <CardTitle className="text-base md:text-2xl font-normal">
                        Similar Producer
                    </CardTitle>
                </div>
            </CardHeader>

            <CardContent className="grid grid-cols-1 md:grid-cols-2 gap-6 px-3 pt-0 pb-0">
                {/* Listening Duration by Genre */}
                <Card className="md:col-span-1 rounded-[27.35px] sm:rounded-[40px] sm:px-3 py-2 sm:py-4 bg-[rgba(255,255,255,0.51)] dark:bg-[#19191950]">
                    <CardHeader className="px-6 py-3">
                        <CardTitle className="text-base sm:text-xl font-medium">
                            Listening Duration by Genre
                        </CardTitle>
                        <CardDescription className="text-sm sm:text-base">
                            Total minutes played per genre
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pt-0 -mt-2 sm:-mt-3">
                        <div className="space-y-2">
                            {durationData.slice(0, 4).map((genre, index) => {
                                const maxDuration = Math.max(
                                    ...durationData.map((d) => d.duration)
                                );
                                const percent = (
                                    (genre.duration / maxDuration) *
                                    100
                                ).toFixed(2);

                                const displayWidth = Math.max(
                                    parseFloat(percent),
                                    25
                                );

                                return (
                                    <div
                                        key={genre.genre}
                                        className="mb-4.5 flex flex-col sm:flex-row sm:gap-4 sm:items-center"
                                    >
                                        <div className="flex justify-between items-center mb-1 min-w-[68px] sm:min-w-[100px]">
                                            <span className="text-sm sm:text-base font-medium">
                                                {genre.genre}
                                            </span>
                                        </div>
                                        <div className="flex relative w-full h-[18px] sm:h-[30px]">
                                            {/* Background track */}
                                            <div className="absolute inset-0 bg-[#AEAEAE]/10 dark:bg-white/10 rounded-[5.93px] sm:rounded-[10px]" />
                                            {/* Filled bar */}
                                            <div
                                                className="absolute top-0 left-0 h-full rounded-[5.93px] sm:rounded-[10px] flex items-center justify-end pr-2 sm:pr-3"
                                                style={{
                                                    width: `${displayWidth}%`,
                                                    backgroundColor: "#EA6115",
                                                    backgroundImage: `repeating-linear-gradient(
                          20deg,
                          transparent,
                          transparent 8px,
                          rgba(255, 255, 255, 0.15) 8px,
                          rgba(255, 255, 255, 0.15) 12px
                        )`,
                                                }}
                                            >
                                                <span className="text-white text-xs sm:text-sm font-medium">
                                                    {genre.duration} min
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                        {durationData.length > 4 && (
                            <div className="mt-6 flex justify-end">
                                <div className="bg-[#EA6115] text-white px-3 py-1 rounded-[40px] text-[10px] sm:text-xs font-medium cursor-default">
                                    +{durationData.length - 4} More Genres
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Collaboration Breakdown */}

                <Card className="md:col-span-1 rounded-[27.35px] sm:rounded-[40px] sm:px-3 py-2 sm:py-4 bg-[rgba(255,255,255,0.51)] dark:bg-[#19191950]">
                    <CardHeader className="px-6 py-3">
                        <CardTitle className="text-base sm:text-xl font-medium">
                            Collaboration Breakdown
                        </CardTitle>
                        <CardDescription className="text-sm sm:text-base">
                            Producers vs Artists Collaborations
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pb-2 -mt-4">
                        {" "}
                        {/* Reduced bottom padding */}
                        <div className="grid grid-cols-2 gap-4 sm:gap-6 lg:gap-8">
                            {/* Producers Chart */}
                            <div className="flex flex-col items-center">
                                <div className="relative w-36 h-36 sm:w-40 sm:h-40 lg:w-64 lg:h-64">
                                    <ChartContainer
                                        config={{
                                            value: {
                                                label: "Producers",
                                                color: "#EA6115",
                                            },
                                        }}
                                        className="w-full h-full"
                                    >
                                        <PieChart width="100%" height="100%">
                                            {/* Background circle */}
                                            <Pie
                                                data={[{ value: 100 }]}
                                                dataKey="value"
                                                startAngle={90}
                                                endAngle={-270}
                                                innerRadius="55%"
                                                outerRadius="85%"
                                                fill="#AEAEAE"
                                                fillOpacity={0.1}
                                                className="dark:fill-white"
                                                strokeWidth={0}
                                            />
                                            {/* Active segment */}
                                            <Pie
                                                data={[
                                                    {
                                                        value:
                                                            ((collaborationBreakdown?.[
                                                                "Producer Collabs"
                                                            ] || 0) /
                                                                ((collaborationBreakdown?.[
                                                                    "Solo"
                                                                ] || 0) +
                                                                    (collaborationBreakdown?.[
                                                                        "Artist Collabs"
                                                                    ] || 0) +
                                                                    (collaborationBreakdown?.[
                                                                        "Producer Collabs"
                                                                    ] || 0))) *
                                                                100 || 0,
                                                    },
                                                ]}
                                                dataKey="value"
                                                startAngle={90}
                                                endAngle={
                                                    90 -
                                                    (360 *
                                                        ((collaborationBreakdown?.[
                                                            "Producer Collabs"
                                                        ] || 0) /
                                                            ((collaborationBreakdown?.[
                                                                "Solo"
                                                            ] || 0) +
                                                                (collaborationBreakdown?.[
                                                                    "Artist Collabs"
                                                                ] || 0) +
                                                                (collaborationBreakdown?.[
                                                                    "Producer Collabs"
                                                                ] || 0))) || 0)
                                                }
                                                innerRadius="55%"
                                                outerRadius="85%"
                                                fill="#EA6115"
                                                strokeWidth={0}
                                                cornerRadius={20}
                                            />
                                        </PieChart>
                                    </ChartContainer>
                                    <div className="absolute inset-0 flex items-center justify-center">
                                        <span className="text-sm sm:text-2xl lg:text-3xl font-normal text-black dark:text-white">
                                            {Math.round(
                                                ((collaborationBreakdown?.[
                                                    "Producer Collabs"
                                                ] || 0) /
                                                    ((collaborationBreakdown?.[
                                                        "Solo"
                                                    ] || 0) +
                                                        (collaborationBreakdown?.[
                                                            "Artist Collabs"
                                                        ] || 0) +
                                                        (collaborationBreakdown?.[
                                                            "Producer Collabs"
                                                        ] || 0))) *
                                                    100
                                            ) || 0}
                                            %
                                        </span>
                                    </div>
                                </div>
                                <p className="mt-0 sm:mt-0 text-xs sm:text-lg font-medium">
                                    Producers
                                </p>
                            </div>

                            {/* Artists Chart */}
                            <div className="flex flex-col items-center">
                                <div className="relative w-36 h-36 sm:w-40 sm:h-40 lg:w-64 lg:h-64">
                                    <ChartContainer
                                        config={{
                                            value: {
                                                label: "Artists",
                                                color: "#EA6115",
                                            },
                                        }}
                                        className="w-full h-full"
                                    >
                                        <PieChart width="100%" height="100%">
                                            {/* Background circle */}
                                            <Pie
                                                data={[{ value: 100 }]}
                                                dataKey="value"
                                                startAngle={90}
                                                endAngle={-270}
                                                innerRadius="55%"
                                                outerRadius="85%"
                                                fill="#AEAEAE"
                                                fillOpacity={0.1}
                                                className="dark:fill-white"
                                                strokeWidth={0}
                                            />
                                            {/* Active segment */}
                                            <Pie
                                                data={[
                                                    {
                                                        value:
                                                            ((collaborationBreakdown?.[
                                                                "Artist Collabs"
                                                            ] || 0) /
                                                                ((collaborationBreakdown?.[
                                                                    "Solo"
                                                                ] || 0) +
                                                                    (collaborationBreakdown?.[
                                                                        "Artist Collabs"
                                                                    ] || 0) +
                                                                    (collaborationBreakdown?.[
                                                                        "Producer Collabs"
                                                                    ] || 0))) *
                                                                100 || 0,
                                                    },
                                                ]}
                                                dataKey="value"
                                                startAngle={90}
                                                endAngle={
                                                    90 -
                                                    (360 *
                                                        ((collaborationBreakdown?.[
                                                            "Artist Collabs"
                                                        ] || 0) /
                                                            ((collaborationBreakdown?.[
                                                                "Solo"
                                                            ] || 0) +
                                                                (collaborationBreakdown?.[
                                                                    "Artist Collabs"
                                                                ] || 0) +
                                                                (collaborationBreakdown?.[
                                                                    "Producer Collabs"
                                                                ] || 0))) || 0)
                                                }
                                                innerRadius="55%"
                                                outerRadius="85%"
                                                fill="#EA6115"
                                                strokeWidth={0}
                                                cornerRadius={20}
                                            />
                                        </PieChart>
                                    </ChartContainer>
                                    <div className="absolute inset-0 flex items-center justify-center">
                                        <span className="text-sm sm:text-2xl lg:text-3xl font-normal text-black dark:text-white">
                                            {Math.round(
                                                ((collaborationBreakdown?.[
                                                    "Artist Collabs"
                                                ] || 0) /
                                                    ((collaborationBreakdown?.[
                                                        "Solo"
                                                    ] || 0) +
                                                        (collaborationBreakdown?.[
                                                            "Artist Collabs"
                                                        ] || 0) +
                                                        (collaborationBreakdown?.[
                                                            "Producer Collabs"
                                                        ] || 0))) *
                                                    100
                                            ) || 0}
                                            %
                                        </span>
                                    </div>
                                </div>
                                <p className="mt-0 sm:mt-0 text-xs sm:text-lg font-medium">
                                    Artists
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                {/* Weekly Listening Duration */}
                <Card className="md:col-span-2 rounded-[27.35px] sm:rounded-[40px] sm:px-3 py-2 sm:py-4 bg-[rgba(255,255,255,0.51)] dark:bg-[#19191950]">
                    <CardHeader className="px-6 py-3 pb-1 sm:pb-3">
                        <CardTitle className="text-base sm:text-xl font-medium">
                            Daily Listening Trend
                        </CardTitle>
                        <CardDescription className="text-sm sm:text-base">
                            Your listening activity over the past days of this
                            week
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {weeklyListeningData &&
                        weeklyListeningData.length > 0 ? (
                            <ChartContainer
                                config={weeklyChartConfig}
                                className="h-[350px]"
                            >
                                <AreaChart
                                    accessibilityLayer
                                    data={weeklyListeningData}
                                    margin={{
                                        top: -2,
                                        left: -20,
                                        right: 12,
                                        bottom: 15,
                                    }}
                                    width={undefined}
                                    height={undefined}
                                >
                                    <defs>
                                        <linearGradient
                                            id="colorDuration"
                                            x1="0"
                                            y1="0"
                                            x2="0"
                                            y2="1"
                                        >
                                            <stop
                                                offset="5%"
                                                stopColor="var(--color-duration)"
                                                stopOpacity={0.3}
                                            />
                                            <stop
                                                offset="95%"
                                                stopColor="var(--color-duration)"
                                                stopOpacity={0}
                                            />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid vertical={false} />
                                    <XAxis
                                        dataKey="day"
                                        tickLine={false}
                                        axisLine={false}
                                        tickMargin={8}
                                    />
                                    <YAxis
                                        tickLine={false}
                                        axisLine={false}
                                        tickMargin={8}
                                    />
                                    <ChartTooltip
                                        cursor={false}
                                        content={<CustomTooltip />}
                                    />
                                    <Area
                                        type="monotone"
                                        dataKey="duration"
                                        stroke="var(--color-duration)"
                                        strokeWidth={2}
                                        fillOpacity={1}
                                        fill="url(#colorDuration)"
                                    />
                                </AreaChart>
                            </ChartContainer>
                        ) : (
                            <div className="flex h-[250px] items-center justify-center text-muted-foreground">
                                No data available for weekly listening duration.
                            </div>
                        )}
                    </CardContent>
                </Card>
            </CardContent>
        </Card>
    );
}
