import React, { useState } from "react";
import { Button } from "@/components/ui/button";
import { router } from "@inertiajs/react";
import { MoveUpRight } from "lucide-react";
import ProducerCard from "./ProducerCard";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "./ui/card";

export default function SimilarProducers({ similarProducers }) {
  const [showAll, setShowAll] = useState(false);

  if (!Array.isArray(similarProducers) || !similarProducers?.length) {
    return (
      <div className="text-muted-foreground text-center py-6">
        No similar producers found
      </div>
    );
  }

  const displayedProducers = showAll ? similarProducers : similarProducers?.slice(0, 4);
  const hasMore = similarProducers?.length > 4;

  return (
    <Card className="rounded-[31.19px] lg:rounded-[47px] px-2 sm:px-6 py-2 sm:py-4 dark:border-none">
      <CardHeader className="px-3 py-3 pb-1 sm:pb-3">
        <div>
          <CardTitle className="text-base md:text-2xl font-normal">
            Similar Producer
          </CardTitle>
        </div>
      </CardHeader>
      <CardContent className="px-3 pt-0 pb-0">
        <div
          className={`grid grid-cols-1 md:grid-cols-[repeat(auto-fill,minmax(340px,1fr))] gap-4 mb-6`}
        >
          {displayedProducers?.map((producer) => (
            <div key={producer.id} >
            <ProducerCard
              producer={{
                producer: producer,
                track_count: producer.track_count || 0,
                total_minutes: producer.total_minutes || 0,
                average_popularity: producer.average_popularity || 0,
                latest_track: producer.latest_track,
                genres: producer.genres || []
              }}
              arrow={true}
              rootClass="bg-white"
              starClass="bg-black/[0.05]"
              trackClass="bg-black/[0.05]"
            /></div>
          ))}
        </div>
      </CardContent>
      {hasMore && !showAll && (
      <CardFooter className="text-sm text-muted-foreground px-6 pb-2 flex justify-end">
        <button onClick={() => setShowAll(true)}>Show More</button>
      </CardFooter>
      )}
    </Card>
  );
}
