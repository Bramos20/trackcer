import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

export default function ProducerFilters({ genres, selectedGenre, filters }) {
  const [localFilters, setLocalFilters] = useState({
    genre: selectedGenre || '',
    sort_by: filters?.sort_by || 'track_count',
    sort_order: filters?.sort_order || 'desc',
    min_tracks: filters?.min_tracks || '',
    min_popularity: filters?.min_popularity || '',
  });

  const handleFilterChange = (key, value) => {
    // Convert special "all" value back to empty string for genre
    const processedValue = key === 'genre' && value === 'all' ? '' : value;
    const newFilters = { ...localFilters, [key]: processedValue };
    setLocalFilters(newFilters);

    // Apply filters immediately
    router.get(route('producers'), {
      ...newFilters,
      // Remove empty values
      genre: newFilters.genre || null,
      min_tracks: newFilters.min_tracks || null,
      min_popularity: newFilters.min_popularity || null,
    }, {
      preserveScroll: true,
      preserveState: false, // Ensure fresh data when filtering
    });
  };

  const clearFilters = () => {
    const clearedFilters = {
      genre: '',
      sort_by: 'track_count',
      sort_order: 'desc',
      min_tracks: '',
      min_popularity: '',
    };
    setLocalFilters(clearedFilters);
    
    router.get(route('producers'), {
      sort_by: 'track_count',
      sort_order: 'desc',
    }, {
      preserveScroll: true,
      preserveState: false, // Ensure fresh data when clearing filters
    });
  };

  return (
    <Card className="mb-6">
      <CardHeader>
        <div className="flex justify-between items-center">
          <CardTitle className="text-lg">Filter & Sort</CardTitle>
          <button
            onClick={clearFilters}
            className="text-sm text-blue-600 hover:text-blue-800"
          >
            Clear All Filters
          </button>
        </div>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          {/* Genre Filter */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Genre
            </label>
            <Select
              value={localFilters.genre || 'all'}
              onValueChange={(value) => handleFilterChange('genre', value)}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder="All Genres" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Genres</SelectItem>
                {genres.map((g) => (
                  <SelectItem key={g} value={g}>
                    {g}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {/* Sort By */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Sort By
            </label>
            <Select
              value={localFilters.sort_by}
              onValueChange={(value) => handleFilterChange('sort_by', value)}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="track_count">Track Count</SelectItem>
                <SelectItem value="popularity">Popularity</SelectItem>
                <SelectItem value="recent">Recently Added</SelectItem>
                <SelectItem value="name">Name</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {/* Sort Order */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Order
            </label>
            <Select
              value={localFilters.sort_order}
              onValueChange={(value) => handleFilterChange('sort_order', value)}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="desc">High to Low</SelectItem>
                <SelectItem value="asc">Low to High</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {/* Minimum Tracks */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Min Tracks
            </label>
            <input
              type="number"
              value={localFilters.min_tracks}
              onChange={(e) => handleFilterChange('min_tracks', e.target.value)}
              placeholder="e.g. 5"
              min="1"
              className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            />
          </div>

          {/* Minimum Popularity */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Min Popularity
            </label>
            <input
              type="number"
              value={localFilters.min_popularity}
              onChange={(e) => handleFilterChange('min_popularity', e.target.value)}
              placeholder="e.g. 50"
              min="0"
              max="100"
              className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            />
          </div>
        </div>

        {/* Active Filters Display */}
        {(localFilters.genre || localFilters.min_tracks || localFilters.min_popularity) && (
          <div className="mt-4 pt-4 border-t border-gray-200">
            <div className="flex flex-wrap gap-2">
              <span className="text-sm text-gray-600">Active filters:</span>
              {localFilters.genre && (
                <span className="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                  Genre: {localFilters.genre}
                </span>
              )}
              {localFilters.min_tracks && (
                <span className="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                  Min Tracks: {localFilters.min_tracks}
                </span>
              )}
              {localFilters.min_popularity && (
                <span className="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">
                  Min Popularity: {localFilters.min_popularity}
                </span>
              )}
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}