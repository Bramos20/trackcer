// Shared types for the application

export interface Producer {
  id: number;
  name: string;
}

export interface Genre {
  id: number;
  name: string;
}

export interface SpotifyTrackData {
  album?: {
    images?: Array<{ url: string }>;
    release_date?: string;
  };
  external_urls?: {
    spotify?: string;
  };
  [key: string]: any;
}

export interface AppleMusicTrackData {
  attributes?: {
    artwork?: {
      url?: string;
      width?: number;
      height?: number;
    };
  };
  [key: string]: any;
}

export interface Track {
  id: string | number;
  track_name: string;
  artist_name: string;
  album_name: string;
  played_at: string;
  source: string;
  track_data: SpotifyTrackData | AppleMusicTrackData | Record<string, any>;
  producers?: Producer[];
  genres?: Genre[];
}

// Type for producer card props
export interface ProducerStat {
  producer: Producer & {
    bio: string;
    image_url: string;
    role: string;
  };
  track_count: number;
  total_minutes: number;
  average_popularity: number;
  latest_track: {
    track_name: string;
    artist_name: string;
    played_at: string;
  };
  genres: string[]; // These are just strings, not the Genre object
}

export interface PaginatedMetaLink {
  label: string;
  url: string | null;
  active: boolean;
}

export interface PaginatedMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links: PaginatedMetaLink[];
}

export interface DemoProducersData {
  data: ProducerStat[];
  meta: PaginatedMeta;
}

// Type for Artist card props
export interface ArtistStat {
  artist_id: string | number;
  artist_name: string;
  image_url: string;
  track_count: number;
  total_minutes: number;
  average_popularity: number;
  role: string;
  latest_track: {
    track_name: string;
    artist_name: string;
    played_at: string;
  };
  genres: string[];
}

export interface DemoArtistsData {
  data: ArtistStat[];
  meta: PaginatedMeta;
}
