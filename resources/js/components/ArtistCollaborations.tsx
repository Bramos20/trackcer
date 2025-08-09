import React, { useState } from "react";
import { Button } from "@/components/ui/button";
import { router } from "@inertiajs/react";
import { Eye, MoveUpRight } from "lucide-react";
import CollabCard from "./CollabCard";
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from "./ui/card";

export default function ArtistCollaborations({
    producerName,
    collaborators,
    producerId,
}) {
    const [showAll, setShowAll] = useState(false);

    if (!collaborators || collaborators.length === 0) {
        return (
            <div className="text-muted-foreground text-center py-6">
                No artist collaborators found.
            </div>
        );
    }

    const displayedArtists = showAll
        ? collaborators
        : collaborators.slice(0, 4);
    const hasMore = collaborators.length > 4;

    return (
        <Card className="rounded-[31.19px] lg:rounded-[47px] px-2 sm:px-6 py-2 sm:py-4">
            <CardHeader className="px-3 py-3 pb-1 sm:pb-3">
                <div>
                    <CardTitle className="text-base md:text-2xl font-normal">
                        Collaborating Artists
                    </CardTitle>
                </div>
            </CardHeader>
            <CardContent className="px-3 pt-0 pb-0">
                <div className="grid grid-cols-1 md:grid-cols-[repeat(auto-fill,minmax(340px,1fr))] gap-4">
                    {displayedArtists.map((artist) => (
                        <CollabCard
                            producer={artist}
                            isProducer={false}
                            producerId={producerId?.toString()}
                            artistName={artist.name}
                        />
                    ))}
                </div>
            </CardContent>
            {hasMore && !showAll && (
                <CardFooter className="text-sm text-muted-foreground px-6 py-2 flex justify-end">
                    <button onClick={() => setShowAll(true)}>Show More</button>
                </CardFooter>
            )}
        </Card>
    );
}
