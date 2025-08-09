import React from "react";
import { Button } from "@/components/ui/button";
import TrackCard1 from "./TrackCard1";
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from "./ui/card";

export default function RecommendedTracks({ tracks }) {
    if (!tracks?.length) {
        return (
            <div className="text-muted-foreground text-center py-10">
                No recommended tracks available.
            </div>
        );
    }

    return (
        <Card className="rounded-[31.19px] lg:rounded-[47px] px-2 sm:px-6 py-2 sm:py-4 dark:border-none">
            <CardHeader className="px-3 py-3 pb-1 sm:pb-3">
                <div>
                    <CardTitle className="text-base md:text-2xl font-normal">
                        Recommend Tracks
                    </CardTitle>
                </div>
            </CardHeader>
            <CardContent className="px-3 pt-0 pb-0">
                <div
                    className={`grid grid-cols-1 md:grid-cols-[repeat(auto-fill,minmax(340px,1fr))] gap-6 mb-6`}
                >
                    {tracks.map((track) => (
                        <div key={track.id}>
                            <TrackCard1 track={track} rtc={true} />
                        </div>
                    ))}
                </div>
            </CardContent>
            {/* {showMoreButton && ( */}
            <CardFooter className="text-sm text-muted-foreground px-6 pb-2 flex justify-end">
                <button>Show More</button>
            </CardFooter>
            {/* )} */}
        </Card>
    );
}
