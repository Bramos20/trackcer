import { Card } from "@/components/ui/card";
import { PlayButton } from "@/components/PlayButton";
import dayjs from "dayjs";

export default function TrackList({ tracks }) {
  if (!tracks?.length) return null;

  return (
    <div className="space-y-4">
      {tracks.map((track) => {
        const {
          id,
          track_name,
          album_name,
          played_at,
          source,
          genres,
          track_data,
          spotify_url,
          apple_music_url,
        } = track;

        const data = JSON.parse(track_data);
        let albumImage = "https://via.placeholder.com/60";

        if (source === "spotify") {
          albumImage = data?.album?.images?.[0]?.url || albumImage;
        } else if (source === "Apple Music") {
          const artwork = data?.attributes?.artwork;
          if (artwork?.url) {
            albumImage = artwork.url
              .replace("{w}", "120")
              .replace("{h}", "120");
          }
        }

        return (
          <Card
            key={id}
            className="bg-gray-800 hover:bg-gray-700 transition-colors p-4"
          >
            <div className="flex items-center gap-4">
              <img
                src={albumImage}
                alt="Album Cover"
                className="w-16 h-16 rounded object-cover"
              />
              <div className="flex-1">
                <h3 className="text-white font-medium">{track_name}</h3>
                <p className="text-gray-300 text-sm">{album_name}</p>
                <p className="text-gray-400 text-xs">
                  Played at: {dayjs(played_at).format("DD MMM YYYY, hh:mm A")}
                </p>
                <p className="text-gray-400 text-xs mt-1">
                  Genres:{" "}
                  {genres?.length ? genres.map((g) => g.name).join(", ") : "Unknown"}
                </p>
              </div>
              <div className="shrink-0">
                <PlayButton
                  spotifyUrl={spotify_url}
                  appleMusicUrl={apple_music_url}
                />
              </div>
            </div>
          </Card>
        );
      })}
    </div>
  );
}