import React from "react";
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from "recharts";
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from "@/components/ui/chart";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { BarChart3 } from "lucide-react";

export default function ProducerAnalytics({ producerLabels = [], producerCounts = [] }) {
  const chartData = producerLabels.map((label, index) => ({
    name: label,
    plays: producerCounts[index] || 0
  }));

  const chartConfig = {
    plays: {
      label: "Plays",
      color: "var(--chart-1)",
    },
  } satisfies ChartConfig;

  return (
    <div className="space-y-4">
      {/* Top Producers */}
      <Card className="w-full overflow-hidden">
        <CardHeader>
          <div className="flex justify-between items-center">
            <div>
              <CardTitle>Top Producers</CardTitle>
              <CardDescription>Most played producers in your collection</CardDescription>
            </div>
            <Button variant="outline" size="sm">View All</Button>
          </div>
        </CardHeader>
        <CardContent className="px-2 sm:px-6 overflow-x-auto">
          <ChartContainer config={chartConfig} className="h-[450px] sm:h-[550px] w-full">
            <BarChart accessibilityLayer data={chartData} margin={{ bottom: 20, left: 10, right: 10, top: 20 }}>
              <CartesianGrid vertical={false} />
              <XAxis
                dataKey="name"
                tickLine={false}
                tickMargin={10}
                axisLine={false}
                angle={-45}
                textAnchor="end"
                height={100}
                tick={{ fontSize: 12 }}
                interval={0}
              />
              <YAxis
                tickLine={false}
                tickMargin={10}
                axisLine={false}
              />
              <ChartTooltip
                cursor={false}
                content={<ChartTooltipContent hideLabel />}
              />
              <Bar dataKey="plays" fill="var(--color-plays)" radius={8} />
            </BarChart>
          </ChartContainer>
        </CardContent>
      </Card>

      {/* Network Graph Placeholder */}
      <Card>
        <CardHeader>
          <CardTitle>Producer Relationships</CardTitle>
          <CardDescription>
            Connections between producers in your library
          </CardDescription>
        </CardHeader>
        <CardContent className="flex items-center justify-center h-64 bg-slate-50 dark:bg-slate-900/40 rounded-md">
          <div className="text-center space-y-3">
            <div className="p-3 rounded-full bg-slate-100 dark:bg-slate-800 inline-block">
              <BarChart3 className="h-6 w-6 text-slate-400" />
            </div>
            <div>
              <p className="font-medium">Network Graph Coming Soon</p>
              <p className="text-sm text-muted-foreground">
                Visualize how your favorite producers collaborate
              </p>
            </div>
            <Button variant="outline" size="sm" disabled>Preview Beta</Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
