"use client";

import * as React from "react";
import { format } from "date-fns";
import { CalendarDays, ChevronDown } from "lucide-react";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { cn } from "@/lib/utils";
import { type DateRange } from "react-day-picker";
import { Calendar } from "./calender";

export function DateRangePicker({
  className,
  onChange,
  defaultValue = { from: undefined, to: undefined },
}: {
  className?: string;
  onChange?: (range: DateRange | undefined) => void;
  defaultValue?: DateRange;
}) {
  const [date, setDate] = React.useState<DateRange | undefined>(defaultValue);

  const handleSelect = (range: DateRange | undefined) => {
    setDate(range);
    if (onChange) onChange(range);
  };

  return (
    <div className={cn("grid gap-2", className)}>
      <Popover>
        <PopoverTrigger asChild>
          <button
            className={cn(
              "w-fit max-w-[260px] h-[35px] md:h-[52px] rounded-full  bg-white dark:bg-[rgb(25,25,25)] text-black dark:text-white text-sm font-medium flex items-center justify-between px-2",
              "hover:bg-gray-50 transition-colors duration-150"
            )}
          >
            <div className="flex items-center space-x-2">
              <div className="dark:bg-[rgb(45,45,45)] bg-[rgb(244,244,244)]  rounded-full aspect-square">
                <CalendarDays className="h-7 w-7 md:h-9 md:w-9 text-black dark:text-white p-1.5" />
              </div>
              <span className="pr-2">
                {date?.from ? (
                  date.to ? (
                    <>
                      {format(date.from, "MMM d, yy")} -{" "}
                      {format(date.to, "MMM d, yy")}
                    </>
                  ) : (
                    format(date.from, "MMM d, yy")
                  )
                ) : (
                  "All time"
                )}
              </span>
            </div>
            <div className="dark:bg-[rgb(45,45,45)] bg-[rgb(244,244,244)] rounded-full aspect-square">
              <ChevronDown className="h-5 w-5 text-black dark:text-white p-1" />
            </div>

          </button>
        </PopoverTrigger>
        <PopoverContent
          className="w-auto p-0 bg-white"
          align="start"
        >
          <Calendar
            initialFocus
            mode="range"
            defaultMonth={date?.from}
            selected={date}
            onSelect={handleSelect}
            numberOfMonths={2}
          />
        </PopoverContent>
      </Popover>
    </div>
  );
}
