import React, { useState } from "react";
import { router } from "@inertiajs/react";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Calendar } from "lucide-react";

const DashboardHeader = ({ timeframe }) => {
  const handleTimeframeChange = (value) => {
    router.visit(route("dashboard"), {
      method: "get",
      data: { timeframe: value },
      preserveScroll: true,
      preserveState: false, // Ensure fresh data when changing timeframe
    });
  };


  return (
    <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
          <h1 className="text-3xl lg:text-5xl lg:mb-1 font-normal">Music Analytics</h1>

          <p className="lg:text-lg text-muted-foreground">
              Your listening statistics and producer insights
          </p>

      </div>

      <div className="flex items-center gap-2">
        <div className="p-0.5 bg-white/[0.51] dark:bg-[#191919]/[0.58] rounded-full">
          <Select defaultValue={timeframe || "all"} onValueChange={handleTimeframeChange}>
            <SelectTrigger className="h-auto py-3 sm:py-5.5 px-4 sm:px-6 w-55 bg-transparent border-0 rounded-full text-sm sm:text-base font-medium">
              <Calendar className="h-4 w-4 mr-2 text-gray-600 dark:text-gray-400" />
              <SelectValue placeholder="Select period" />
            </SelectTrigger>
            <SelectContent className="bg-white/[0.51] dark:bg-[#191919]/[0.58] backdrop-blur-md rounded-2xl border-0 p-2">
              <SelectItem value="today" className="rounded-sm data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white mb-1">
                Today
              </SelectItem>
              <SelectItem value="last_2_days" className="rounded-sm data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white mb-1">
                Last 2 Days
              </SelectItem>
              <SelectItem value="last_week" className="rounded-sm data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white mb-1">
                Last Week
              </SelectItem>
              <SelectItem value="last_month" className="rounded-sm data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white mb-1">
                Last Month
              </SelectItem>
              <SelectItem value="last_year" className="rounded-sm data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white mb-1">
                Last Year
              </SelectItem>
              <SelectItem value="all" className="rounded-sm data-[state=checked]:bg-[#6A4BFB] data-[state=checked]:text-white">
                All Time
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
    </div>
  );
};

export default DashboardHeader;
