import React from "react"
import { Card, CardHeader, CardFooter, CardTitle, CardContent, CardDescription } from "@/components/ui/card"
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from "recharts"
import { ChartConfig, ChartContainer, ChartTooltip } from "@/components/ui/chart"
import { differenceInDays, format } from 'date-fns'
import { useTheme } from "next-themes"

export default function TopProducersChart({ topProducers = [], start, end }) {
  const { theme } = useTheme();
  console.log('TopProducersChart - topProducers:', topProducers);

  const chartData = topProducers.map(p => ({
    name: p.name,
    plays: p.total_play_count || p.play_count || 0,
    image_url: p.image_url || null
  }))

  console.log('TopProducersChart - chartData:', chartData);

  const chartConfig = {
    plays: {
      label: "Tracks Played:  ",
      color: theme === 'dark' ? "#231A55" : "#6A4BFB",
    },
  } satisfies ChartConfig

  let rangeLabel = 'All Time'
  if (start && end) {
    const startDate = new Date(start)
    const endDate = new Date(end)
    const dayCount = differenceInDays(endDate, startDate) + 1
    rangeLabel = `${format(startDate, 'MMM d, yyyy')} to ${format(endDate, 'MMM d, yyyy')} (${dayCount} days)`
  }

  if (!topProducers || topProducers.length === 0) {
    return (
      <div className="text-center py-10 text-muted-foreground">
        <p>No data available.</p>
      </div>
    );
  }

  return (
    <Card className="rounded-[31.19px] lg:rounded-[47px] px-3 sm:px-6 py-2 sm:py-4">
      <CardHeader className="px-6 py-3 pb-1 sm:pb-3">
        <div>
          <CardTitle className="text-base sm:text-xl font-medium">Top 10 Producers by Total Plays</CardTitle>
          <CardDescription className="text-sm sm:text-base">Showing the most played producers</CardDescription>
        </div>
      </CardHeader>
      <CardContent className="px-6 pt-0 pb-0">
        <ChartContainer config={chartConfig} className="h-[300px] sm:h-[400px] md:h-[450px] lg:h-[550px] w-full">
          <BarChart accessibilityLayer data={chartData} margin={{ top: 5, right: 9, bottom: 10, left: -25 }}>
            <defs>
              <pattern id="stripes-light-stats" patternUnits="userSpaceOnUse" width="30" height="30">
                <rect width="30" height="30" fill="#6A4BFB" />
                <path d="M0,30 L30,0 M-10,10 L10,-10 M20,40 L40,20" stroke="#FFFFFF" strokeWidth="2" />
              </pattern>
              <pattern id="stripes-dark-stats" patternUnits="userSpaceOnUse" width="30" height="30">
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
              angle={-45}
              textAnchor="end"
              height={120}
              tick={{ fontSize: 13 }}
              interval={0}
            />
            <YAxis
              tickLine={false}
              axisLine={false}
              tickFormatter={(value) => Math.floor(value).toString()}
              domain={[0, 'dataMax']}
              tick={{ dx: -5, fontSize: 13 }}
            />
            <ChartTooltip
              cursor={false}
              content={({ active, payload }) => {
                if (active && payload && payload.length) {
                  const data = payload[0].payload;
                  return (
                      <div className="bg-[#EA6115] p-3 shadow-lg border border-[#EA6115]" style={{ borderRadius: '25.74px' }}>
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
                            Tracks Played: <span className="font-semibold text-[#F6BB9A]">{data.plays}</span>
                          </p>
                        </div>
                      </div>
                    </div>
                  );
                }
                return null;
              }}
            />
            <Bar dataKey="plays" fill={theme === 'dark' ? "url(#stripes-dark-stats)" : "url(#stripes-light-stats)"} radius={8} />
          </BarChart>
        </ChartContainer>
      </CardContent>
    </Card>
  )
}
