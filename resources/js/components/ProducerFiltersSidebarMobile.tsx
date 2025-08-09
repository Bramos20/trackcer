import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Filter, ChevronDown, ChevronUp } from 'lucide-react';

export default function ProducerFiltersSidebarMobile({ genres, selectedGenre, filters, isOpen, onClose }) {
  const [localFilters, setLocalFilters] = useState({
    genre: selectedGenre || '',
    sort_by: filters?.sort_by || 'track_count',
    sort_order: filters?.sort_order || 'desc',
    min_tracks: filters?.min_tracks || '',
    min_popularity: filters?.min_popularity || '',
  });

  const [expandedSections, setExpandedSections] = useState({
    genre: true,
    sortBy: true,
    order: true,
    minTracks: true,
    minPopularity: true,
  });

  const toggleSection = (section) => {
    setExpandedSections(prev => ({
      ...prev,
      [section]: !prev[section]
    }));
  };

  const handleFilterChange = (key, value) => {
    const processedValue = key === 'genre' && value === 'all' ? '' : value;
    const newFilters = { ...localFilters, [key]: processedValue };
    setLocalFilters(newFilters);

    router.get(route('producers'), {
      ...newFilters,
      genre: newFilters.genre || null,
      min_tracks: newFilters.min_tracks || null,
      min_popularity: newFilters.min_popularity || null,
    }, {
      preserveScroll: true,
      preserveState: false,
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
      preserveState: false,
    });
  };

  const sortByOptions = [
    { value: 'track_count', label: 'Track Count' },
    { value: 'popularity', label: 'Popularity' },
    { value: 'recent', label: 'Recently Added' },
    { value: 'name', label: 'Name' },
  ];

  const orderOptions = [
    { value: 'desc', label: 'High to Low' },
    { value: 'asc', label: 'Low to High' },
  ];

  const minTrackOptions = [
    { value: '', label: 'Any' },
    { value: '5', label: '5+' },
    { value: '10', label: '10+' },
    { value: '20', label: '20+' },
    { value: '50', label: '50+' },
  ];

  const minPopularityOptions = [
    { value: '', label: 'Any' },
    { value: '25', label: '25%+' },
    { value: '50', label: '50%+' },
    { value: '75', label: '75%+' },
    { value: '90', label: '90%+' },
  ];

  return (
    <div className={`
      ${isOpen ? 'block' : 'hidden'}
      w-full
      bg-white/42 dark:bg-[#191919]/58
      backdrop-blur-md
      rounded-[25px]
      transition-all duration-300 ease-in-out
      overflow-hidden
      relative
    `}>
      <div className="p-4 h-full overflow-y-auto">
        {/* Header */}
        <div className="mb-3">
          <div className="flex items-center justify-between mb-3">
            <div className="flex items-center gap-2 text-gray-900 dark:text-white">
              <Filter className="w-5 h-5" />
              <h2 className="text-lg font-medium">Filter & Sort</h2>
            </div>
            {onClose && (
              <button
                onClick={onClose}
                className="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
              >
                ×
              </button>
            )}
          </div>
          <div className="border-b border-gray-200 dark:border-gray-700"></div>
        </div>

      {/* Genre Filter */}
      <div className="mb-3">
        <button
          onClick={() => toggleSection('genre')}
          className="w-full flex items-center justify-between mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >
          <span className="text-left">All Genres</span>
          {expandedSections.genre ? <ChevronUp className="w-4 h-4 flex-shrink-0" /> : <ChevronDown className="w-4 h-4 flex-shrink-0" />}
        </button>

        {expandedSections.genre && (
          <div className="space-y-1 max-h-32 overflow-y-auto pl-0 w-full">
            <label className="flex items-start gap-2 cursor-pointer group w-full py-1">
              <input
                type="radio"
                name="genre"
                value=""
                checked={!localFilters.genre}
                onChange={() => handleFilterChange('genre', '')}
                className="w-4 h-4 min-w-[16px] min-h-[16px] flex-shrink-0 accent-[#6A4BFB] cursor-pointer mt-0.5"
              />
              <span className="text-xs text-gray-900 dark:text-gray-100 text-left font-normal leading-tight">All Genres</span>
            </label>
            {genres.map((genre) => (
              <label key={genre} className="flex items-start gap-2 cursor-pointer group w-full py-1">
                <input
                  type="radio"
                  name="genre"
                  value={genre}
                  checked={localFilters.genre === genre}
                  onChange={() => handleFilterChange('genre', genre)}
                  className="w-4 h-4 min-w-[16px] min-h-[16px] flex-shrink-0 accent-[#6A4BFB] cursor-pointer mt-0.5"
                />
                <span className="text-xs text-gray-900 dark:text-gray-100 text-left font-normal leading-tight break-words">{genre}</span>
              </label>
            ))}
          </div>
        )}
        <div className="border-b border-gray-200 dark:border-gray-700 mt-2"></div>
      </div>

      {/* Sort By */}
      <div className="mb-3">
        <button
          onClick={() => toggleSection('sortBy')}
          className="w-full flex items-center justify-between mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >
          <span>Sort by</span>
          {expandedSections.sortBy ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
        </button>

        {expandedSections.sortBy && (
          <div className="space-y-1 pl-0 w-full">
            {sortByOptions.map((option) => (
              <label key={option.value} className="flex items-start gap-2 cursor-pointer group w-full py-1">
                <input
                  type="radio"
                  name="sortBy"
                  value={option.value}
                  checked={localFilters.sort_by === option.value}
                  onChange={() => handleFilterChange('sort_by', option.value)}
                  className="w-4 h-4 min-w-[16px] min-h-[16px] flex-shrink-0 accent-[#6A4BFB] cursor-pointer mt-0.5"
                />
                <span className="text-xs text-gray-900 dark:text-gray-100 text-left font-normal leading-tight">{option.label}</span>
              </label>
            ))}
          </div>
        )}
        <div className="border-b border-gray-200 dark:border-gray-700 mt-2"></div>
      </div>

      {/* Order */}
      <div className="mb-3">
        <button
          onClick={() => toggleSection('order')}
          className="w-full flex items-center justify-between mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >
          <span>Order</span>
          {expandedSections.order ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
        </button>

        {expandedSections.order && (
          <div className="space-y-1 pl-0 w-full">
            {orderOptions.map((option) => (
              <label key={option.value} className="flex items-start gap-2 cursor-pointer group w-full py-1">
                <input
                  type="radio"
                  name="order"
                  value={option.value}
                  checked={localFilters.sort_order === option.value}
                  onChange={() => handleFilterChange('sort_order', option.value)}
                  className="w-4 h-4 min-w-[16px] min-h-[16px] flex-shrink-0 accent-[#6A4BFB] cursor-pointer mt-0.5"
                />
                <span className="text-xs text-gray-900 dark:text-gray-100 text-left font-normal leading-tight">{option.label}</span>
              </label>
            ))}
          </div>
        )}
        <div className="border-b border-gray-200 dark:border-gray-700 mt-2"></div>
      </div>

      {/* Min Tracks */}
      <div className="mb-3">
        <button
          onClick={() => toggleSection('minTracks')}
          className="w-full flex items-center justify-between mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >
          <span>Min. Tracks</span>
          {expandedSections.minTracks ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
        </button>

        {expandedSections.minTracks && (
          <div className="space-y-1 pl-0 w-full">
            {minTrackOptions.map((option) => (
              <label key={option.value} className="flex items-start gap-2 cursor-pointer group w-full py-1">
                <input
                  type="radio"
                  name="minTracks"
                  value={option.value}
                  checked={localFilters.min_tracks === option.value}
                  onChange={() => handleFilterChange('min_tracks', option.value)}
                  className="w-4 h-4 min-w-[16px] min-h-[16px] flex-shrink-0 accent-[#6A4BFB] cursor-pointer mt-0.5"
                />
                <span className="text-xs text-gray-900 dark:text-gray-100 text-left font-normal leading-tight">{option.label}</span>
              </label>
            ))}
          </div>
        )}
        <div className="border-b border-gray-200 dark:border-gray-700 mt-2"></div>
      </div>

      {/* Min Popularity */}
      <div className="mb-3">
        <button
          onClick={() => toggleSection('minPopularity')}
          className="w-full flex items-center justify-between mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >
          <span>Min Popularity</span>
          {expandedSections.minPopularity ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
        </button>

        {expandedSections.minPopularity && (
          <div className="space-y-1 pl-0 w-full">
            {minPopularityOptions.map((option) => (
              <label key={option.value} className="flex items-start gap-2 cursor-pointer group w-full py-1">
                <input
                  type="radio"
                  name="minPopularity"
                  value={option.value}
                  checked={localFilters.min_popularity === option.value}
                  onChange={() => handleFilterChange('min_popularity', option.value)}
                  className="w-4 h-4 min-w-[16px] min-h-[16px] flex-shrink-0 accent-[#6A4BFB] cursor-pointer mt-0.5"
                />
                <span className="text-xs text-gray-900 dark:text-gray-100 text-left font-normal leading-tight">{option.label}</span>
              </label>
            ))}
          </div>
        )}
      </div>

      {/* Clear Filters Button */}
      {(localFilters.genre || localFilters.min_tracks || localFilters.min_popularity ||
        localFilters.sort_by !== 'track_count' || localFilters.sort_order !== 'desc') && (
        <div className="pt-3 border-t border-gray-200 dark:border-gray-700">
          <button
            onClick={clearFilters}
            className="w-full px-4 py-2 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded-lg text-sm font-medium transition-colors text-gray-900 dark:text-white"
          >
            Clear All Filters
          </button>
        </div>
      )}
      </div>
    </div>
  );
}