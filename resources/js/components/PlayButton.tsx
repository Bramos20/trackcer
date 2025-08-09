export function PlayButton({ spotifyUrl, appleMusicUrl }) {
    if (spotifyUrl) {
      return (
        <a
          href={spotifyUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center px-3 py-1 rounded-md bg-green-900 text-green-400 hover:bg-green-800 transition-colors text-sm"
        >
          Play on Spotify
        </a>
      );
    }
  
    if (appleMusicUrl) {
      return (
        <a
          href={appleMusicUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center px-3 py-1 rounded-md bg-red-900 text-red-400 hover:bg-red-800 transition-colors text-sm"
        >
          Play on Apple Music
        </a>
      );
    }
  
    return null;
  }
  