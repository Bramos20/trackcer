import React from "react";
import AppLayout from "@/Layouts/AppLayout";
import { Head } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { ArrowLeft } from "lucide-react";
import TrackCard1 from "@/components/TrackCard1";

export default function SharedTracks({ producer, collaborator, 
  sharedTracks,
   auth }) {
  return (
    <AppLayout user={auth.user}>
      <Head title={`Shared Tracks - ${producer.name} & ${collaborator.name}`} />

      <div className="space-y-6">
        {/* Back Button */}
        <Button
          onClick={() => window.history.back()}
          className="group flex items-center gap-3 !px-6 !py-0 bg-[#F4F4F4]/[0.86] dark:bg-[#191919]/[0.51] backdrop-blur-md rounded-full text-gray-900 dark:text-white hover:bg-white/70 dark:hover:bg-[#191919]/70 transition-all duration-300"
        >
          <ArrowLeft size={20} className="group-hover:-translate-x-1 transition-transform duration-300" />
          <span className="font-medium">Back</span>
        </Button>

        {/* Main Content Container */}
        <div className="relative bg-[#F4F4F4]/[0.86] dark:bg-[#191919]/[0.51] rounded-[27.35px] sm:rounded-[40px] p-6 sm:p-8 pb-0 overflow-hidden">
          <h1 className="text-2xl sm:text-3xl font-normal mb-6">
            Tracks shared by {producer?.name} & {collaborator?.name}
          </h1>

          {sharedTracks.length === 0 ? (
            <div className="flex items-center justify-center py-20">
              <p className="text-muted-foreground text-center">
                No shared tracks found between these producers.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-[repeat(auto-fill,minmax(340px,1fr))] gap-6 mb-6">
              {sharedTracks.map((track, index) => (
                <div key={track.id} className="transition-all duration-300">
                  <TrackCard1
                    track={track}
                    viewMode="grid"
                    index={index}
                    rtc={true}
                  />
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
