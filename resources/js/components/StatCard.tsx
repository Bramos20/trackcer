import React from "react";
import { useIsMobile } from "@/hooks/use-mobile";
import { Music, Users, Clock, TrendingUp, MicVocal } from "lucide-react";
import { cn } from "@/lib/utils";
interface StatCardProps {
    title: string;
    value?: string | number;
    valueGray?: string | number;
    badge?: string;
    arrow?: boolean;
    footer: string;
    isAlbum?: boolean;
    albumCover?: string;
    artistName?: string;
    artistDescription?: string;
    left?: string;
}

export default function StatCard({
    title,
    value,
    valueGray,
    badge,
    arrow = false,
    footer,
    isAlbum = false,
    albumCover,
    artistName,
    artistDescription,
}: StatCardProps) {

    console.log('StatCard rendered with:', albumCover)
    const mobile = useIsMobile(1024);
    // Determine icon component and styling based on title
    const getIconComponent = () => {
        if (title === "Total Tracks") {
            return (
                <Music className="w-4 md:w-5 h-4 md:h-5 text-gray-600 dark:text-gray-400" />
            );
        } else if (title === "Artist Collabs") {
            return (
                <MicVocal className="w-4 md:w-5 h-4 md:h-5 text-gray-600 dark:text-gray-400" />
            );
        } else if (
            title === "Total Producer" ||
            title === "Total Producers" ||
            title === "Producer Collabs"
        ) {
            return (
                <Users className="w-4 md:w-5 h-4 md:h-5 text-gray-600 dark:text-gray-400" />
            );
        } else if (title === "Most Recent") {
            return (
                <Clock className="w-4 md:w-5 h-4 md:h-5 text-gray-600 dark:text-gray-400" />
            );
        } else if (title === "Listening Time") {
            return (
                <TrendingUp className="w-4 md:w-5 h-4 md:h-5 text-gray-600 dark:text-gray-400" />
            );
        }
        return null;
    };

    // determine the left position for the badge
    const badgeLeftPosition = () => {
        if (value && valueGray) {
            if (mobile) {
                return `${value.toString().length * 22 +
                    (valueGray.toString().length || 0) * 22
                    }px`;
            }
            return `${value.toString().length * 35 +
                (valueGray.toString().length || 0) * 35
                }px`;
        }
        return "0px";
    };

    return (
        <div
            className="w-full h-full p-4 rounded-[44px] topRightMain relative bg-[rgba(255,255,255,0.51)] dark:bg-[#19191950]"
            style={{
                borderTopRightRadius: "0rem",
                backdropFilter: "blur(40px)",
                WebkitMask:
                    'url("/assets/top-right.png") center / contain no-repeat, linear-gradient(#000000 0 0)',
                maskSize: "6rem 4rem",
                maskPosition: "top right",
                maskComposite: "exclude",
            }}
        >
            <div className="flex items-center gap-2.5 md:gap-4">
                <span className="w-9 h-9 md:w-12 md:h-12 rounded-full p-2 flex justify-center items-center bg-[rgb(228,228,228)] dark:bg-[#1f1f24]">
                    {getIconComponent()}
                </span>
                <span className="text-sm md:text-lg lg:text-xl">{title}</span>
            </div>
            {!isAlbum ? (
                <div className="text-4xl lg:text-[52px] ml-10 mt-5 md:mt-7 mb-3 md:mb-5 relative flex items-start">
                    <p className="flex">
                        <span>{value}</span>{" "}
                        <span className="text-neutral-400">
                            {valueGray ? valueGray : ""}
                        </span>
                    </p>
                    <div className='flex items-start flex-col pl-1'>

                        {badge && (
                            <p
                                className="px-2 bg-orange-500 rounded-full text-xs py-px inline-block text-white font-normal"
                                style={{ left: badgeLeftPosition() }}
                            >
                                {badge}
                            </p>
                        )}
                        {arrow && (
                            <span
                                className={cn("text-green-500 text-lg mt-2.5", badge ? "mt-2.5" : "mt-7")}
                                style={{ left: badgeLeftPosition() }}
                            >
                                {" "}
                                ↑{" "}
                            </span>
                        )}
                    </div>
                </div>
            ) : (
                <div className="ml-10 mt-7 mb-5 flex items-center gap-2">
                    <div>
                        <img
                            src={
                                albumCover
                            }
                            className="w-14 h-14 object-cover rounded-[2px] lg:rounded-[4px]"
                        />
                    </div>
                    <div>
                        <p className="text-lg font-semibold">{artistName}</p>
                        <p className="text-[#BDBDBD]">{artistDescription}</p>
                    </div>
                </div>
            )}
            <div className="md:text-xl relative mb-3">
                <span className="absolute top-0 left-3 h-full w-2 bg-orange-500 rounded-full"></span>
                <span className="inline-block ml-10">{footer}</span>
            </div>
        </div>
    );
}
