import React from 'react'
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card'
import { Bar, BarChart, XAxis, YAxis } from 'recharts'
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart'

export default function TopWeeklyProducersChart({ weeklyTopProducers = [] }) {
  const chartData = weeklyTopProducers.map(p => ({
    name: p.name,
    plays: p.play_count
  }))

  const chartConfig = {
    plays: {
      label: "Weekly Plays",
      color: "var(--chart-1)",
    },
  } satisfies ChartConfig 

  return (
    <Card className="w-full overflow-hidden">
      <CardHeader>
        <CardTitle>Top Producers This Week</CardTitle>
        <CardDescription>Most played producers in the last 7 days</CardDescription>
      </CardHeader>
      <CardContent className="px-2 sm:px-6 overflow-x-auto">
        <ChartContainer config={chartConfig} className="h-[450px] sm:h-[600px] w-full">
          <BarChart
            accessibilityLayer
            data={chartData}
            layout="vertical"
            margin={{
              left: 10,
              right: 30,
              top: 10,
              bottom: 10,
            }}
          >
            <XAxis type="number" dataKey="plays" hide />
            <YAxis
              dataKey="name"
              type="category"
              tickLine={false}
              tickMargin={10}
              axisLine={false}
              width={150}
              tick={{ fontSize: 11, className: "text-xs sm:text-sm" }}
            />
            <ChartTooltip
              cursor={false}
              content={<ChartTooltipContent hideLabel />}
            />
            <Bar dataKey="plays" fill="var(--color-plays)" radius={5} />
          </BarChart>
        </ChartContainer>
      </CardContent>
    </Card>
  )
}