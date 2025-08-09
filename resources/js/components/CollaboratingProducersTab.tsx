import React, { useState } from "react";
import { router } from "@inertiajs/react";
import CollabCard from "./CollabCard";
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from "./ui/card";

export default function CollaboratingProducersTab({
    producers,
    producerId,
    artistName = null,
}) {
    const [showAll, setShowAll] = useState(false);

    if (!producers || producers.length === 0) {
        return (
            <div className="text-muted-foreground text-center py-6">
                No collaborating producers found.
            </div>
        );
    }

    const displayedProducers = showAll ? producers : producers.slice(0, 4);
    const hasMore = producers.length > 4;

    return (
        <Card className="rounded-[31.19px] lg:rounded-[47px] px-2 sm:px-6 py-2 sm:py-4">
            <CardHeader className="px-3 py-3 pb-1 sm:pb-3">
                <div>
                    <CardTitle className="text-base md:text-2xl font-normal">
                        Collaborating Producers
                    </CardTitle>
                </div>
            </CardHeader>
            <CardContent className="px-3 pt-0 pb-0">
                <div className="grid grid-cols-1 md:grid-cols-[repeat(auto-fill,minmax(340px,1fr))] gap-4">
                    {displayedProducers.map((producer) => (
                        <CollabCard
                            key={producer.id}
                            producer={producer}
                            isProducer={!!producerId}
                            producerId={producer.id?.toString()}
                            artistName={artistName}
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
