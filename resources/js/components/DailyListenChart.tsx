import React from "react";
import { TrendingUp, TrendingDown, Music } from "lucide-react";
import { CartesianGrid, XAxis, YAxis, Area, AreaChart, Tooltip } from "recharts";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  ChartConfig,
  ChartContainer,
  ChartTooltip,
} from "@/components/ui/chart";
import { useIsMobile } from "@/hooks/use-mobile";
import { useTheme } from "next-themes";

export default function DailyListenChart({ listensPerDay }) {
  console.log('DailyListenChart received listensPerDay:', listensPerDay);
  
  // Transform data for recharts
  const chartData = listensPerDay?.map((item: any) => {
    const date = new Date(item.date);
    return {
      date: date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
      fullDate: item.date,
      listens: item.count,
    };
  }) || [];

  const chartConfig = {
    listens: {
      label: "Songs Listened",
      color: "var(--chart-1)",
    },
  } satisfies ChartConfig;

  // Calculate trend
  const lastWeek = chartData.slice(-7);
  const previousWeek = chartData.slice(-14, -7);
  const lastWeekAvg = lastWeek.reduce((sum: number, day: any) => sum + day.listens, 0) / lastWeek.length;
  const previousWeekAvg = previousWeek.length > 0
    ? previousWeek.reduce((sum: number, day: any) => sum + day.listens, 0) / previousWeek.length
    : lastWeekAvg;
  const trendPercentage = previousWeekAvg !== 0
    ? ((lastWeekAvg - previousWeekAvg) / previousWeekAvg * 100).toFixed(1)
    : 0;

  const { theme } = useTheme();
  const mobile = useIsMobile(640);
  
  // Calculate dynamic data
  const totalListens = listensPerDay?.reduce((sum: number, item: any) => sum + item.count, 0) || 0;
  const today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format
  const todayData = listensPerDay?.find((item: any) => item.date === today);
  const todayListens = todayData?.count || 0;
  
  console.log('=== Debug Info ===');
  console.log('listensPerDay:', listensPerDay);
  console.log('totalListens:', totalListens);
  console.log('today:', today);
  console.log('todayData:', todayData);
  console.log('todayListens:', todayListens);
  
  // Split total count for display (first part and remaining digits)
  const totalString = totalListens.toString();
  let firstPart, secondPart;
  
  if (totalString.length === 1) {
    firstPart = totalString;
    secondPart = "";
  } else if (totalString.length === 2) {
    firstPart = totalString.slice(0, 1);
    secondPart = totalString.slice(1);
  } else {
    // For 3+ digits, split roughly in half
    const splitPoint = Math.ceil(totalString.length / 2);
    firstPart = totalString.slice(0, splitPoint);
    secondPart = totalString.slice(splitPoint);
  }
  
  console.log('totalString:', totalString);
  console.log('firstPart:', firstPart, 'secondPart:', secondPart);
  
  // determine the left position for the badge
  const badgeLeftPosition = (value: number | string) => {
    if (mobile) {
      return `${value.toString().length * 41 + (value.toString().length || 0) * 41}px`;
    }
    return `${value.toString().length * 51 + (value.toString().length || 0) * 51}px`;
  };

  return (
   <Card className="relative w-full rounded-[31.19px] rounded-tr-none lg:rounded-[47px] lg:rounded-tr-none !px-3 md:!px-6 py-2 sm:py-4 mt-20 md:mt-16 lg:-mt-4">
      <CardHeader className="!px-3 md:!px-6 py-3 pb-1 sm:pb-3">
        <CardTitle className="text-base sm:text-xl font-medium">Daily Listening Trend</CardTitle>
        <CardDescription className="text-sm sm:text-base">Your listening activity over the past {chartData.length} days</CardDescription>
      </CardHeader>
      <CardContent className="!px-0 !pr-1.5 md:!px-3">
        <ChartContainer
          config={chartConfig}
          className="h-[300px] w-full"
        >
          <AreaChart
            accessibilityLayer
            data={chartData}
            margin={{
              top: 20,
              left: -20,
              right: 12,
            }}
            width={undefined}
            height={undefined}
          >
            <defs>
              <linearGradient
                id="colorListens"
                x1="0"
                y1="0"
                x2="0"
                y2="1"
              >
                <stop
                  offset="5%"
                  stopColor="var(--color-listens)"
                  stopOpacity={0.3}
                />
                <stop
                  offset="95%"
                  stopColor="var(--color-listens)"
                  stopOpacity={0}
                />
              </linearGradient>
            </defs>
            <CartesianGrid
              vertical={false}
              stroke="rgba(0,0,0,0.1)"
              className="stroke-[rgba(0,0,0,0)] dark:stroke-[rgba(255,255,255,0.2)]"
            />
            <XAxis
              dataKey="date"
              tickLine={false}
              axisLine={false}
              tickMargin={8}
              tickFormatter={(value: string) => value}
            />
            <YAxis
              tickLine={false}
              axisLine={false}
              tickMargin={8}
            />
            {/* <ChartTooltip
              cursor={false}
              content={<ChartTooltipContent indicator="line" />}
            /> */}
            <Tooltip
              cursor={false}
              content={({ active, payload }) => {
                if (!active || !payload || !payload.length) return null;

                const data = payload[0].payload;
                return (
                  <div className="px-4 md:px-5 py-1 md:py-2 bg-secondary text-white rounded-full">
                    <p className="text-sm">Total Plays: {data.listens}</p>
                  </div>
                );
              }}
            />
            <Area
              type="monotone"
              dataKey="listens"
              stroke="var(--color-listens)"
              strokeWidth={2}
              fillOpacity={1}
              fill="url(#colorListens)"
            />
          </AreaChart>
        </ChartContainer>
      </CardContent>
      <CardFooter className="flex-col items-start gap-2 text-sm py-4">
        <CardTitle className="text-base sm:text-xl font-medium">
          <div className="flex gap-2">
            {Number(trendPercentage) > 0 ? (
              <>
                Trending up by {Math.abs(Number(trendPercentage))}% this week
                <TrendingUp className="h-4 w-4" />
              </>
            ) : Number(trendPercentage) < 0 ? (
              <>
                Trending down by {Math.abs(Number(trendPercentage))}% this week
                <TrendingDown className="h-4 w-4" />
              </>
            ) : (
              "Stable listening pattern this week"
            )}
          </div>
        </CardTitle>
        <CardDescription className="text-sm sm:text-base">
          Average of <span className="bg-[rgb(234,97,21)] text-gray-200 rounded-full px-2">{lastWeekAvg.toFixed(1)}</span> songs per day this week
        </CardDescription>
      </CardFooter>
      {/* curve image  */}
      <span className="absolute -top-[70px] sm:-top-[78px] right-0 scale-[0.8] origin-right sm:scale-100">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 280 78"
          width="280"
          height="78"
        >
          <path
            d="m262 79.8h-262c10.6-0.4 32.2-9.1 34-40.2 1.8-31.2 25.8-39 37.6-39h176c24.3 0 32.4 8.1 32.4 22.5v56.7z"
            style={{
              fill: theme === "dark" ? "#191919" : "#F4F4F4",
              opacity: theme === "dark" ? 0.51 : 0.86,
              backdropFilter: "blur(40px)",
              WebkitBackdropFilter: "blur(40px)",
            }}
          />
        </svg>
      </span>
      {/* curve content  */}
      <div className="absolute -top-[52px] sm:-top-[65px] right-[35px] sm:right-[83px] flex items-center gap-3 z-[11] origin-right sm:scale-100 max-w-[152px] w-full">
        <div className="relative flex gap-1 justify-between">
          <span className="w-10 h-10 sm:w-12 sm:h-12 rounded-full p-2 flex justify-center items-center bg-[rgba(255,255,255,0.51)] dark:bg-[#19191950] aspect-square">
            <Music className="min-w-5 max-h-5 aspect-square text-gray-600 dark:text-gray-400" />
          </span>
          <div className="text-4xl sm:text-5xl xl:text-[55px] flex-1 flex justify-between">
            {/* //  total count of listens per day */}
            <p className="flex">
              <span>{firstPart || '0'}</span> {secondPart && <span className="text-neutral-400">{secondPart}</span>}
            </p>

            {/* // total count of listens today badge */}
            <div className=" h-fit flex flex-col pl-1">

              <div
                className="px-1 bg-orange-500 rounded-full text-[11px] py-1 inline-block text-white font-normal  w-fit whitespace-nowrap -mt-1"
              // style={{ left: '72px' }}
              >
                +{todayListens} Today
              </div>
              <span
                className="text-green-500 text-lg mt-1.5"
              // style={{ left: '72px' }}
              >
                ↑
              </span>
            </div>
          </div>

        </div>
      </div>
    </Card>
  );
}
