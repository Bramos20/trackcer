import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Filter, ChevronDown, ChevronUp } from 'lucide-react';

export default function ProducerFiltersSidebar({ genres, selectedGenre, filters, isOpen }) {
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
    <div
      className={`
      ${isOpen ? "block absolute lg:relative z-30 top-48 right-5 lg:right-0 lg:z-0 lg:top-0" : "hidden"}
      w-[290px] lg:w-full
      bg-[rgba(255,255,255,0.51)] dark:bg-[#19191950]
      backdrop-blur-2xl
      rounded-[25px] p-1 sm:p-0
      transition-all duration-300 ease-in-out
    `}
    >
            <div className="p-3 sm:p-6 h-full overflow-y-auto">
                {/* Header */}
                <div className="mb-4">
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-2 text-black dark:text-white flex-1">
                            <Filter className="w-5 h-5 flex-shrink-0" />
                            <h2 className="text-lg font-medium">Filter & Sort</h2>
                        </div>
                    </div>
                    <div className="border-b border-gray-200 dark:border-gray-700"></div>
                </div>

                {/* Genre Filter */}
                <div className="mb-4">
                    <button
                        onClick={() => toggleSection('genre')}
                        className="w-full flex items-center justify-between mb-3 text-sm font-medium text-black dark:text-white gap-2"
                    >
                        <span className="text-left">All Genres</span>
                        {expandedSections.genre ? <ChevronUp className="w-4 h-4 flex-shrink-0" /> : <ChevronDown className="w-4 h-4 flex-shrink-0" />}
                    </button>

                    {expandedSections.genre && (
                        <div className="space-y-2 max-h-48 overflow-y-auto pl-0 w-full">
                            <label className="flex items-center gap-3 cursor-pointer group w-full pr-2">
                                <input
                                    type="radio"
                                    name="genre"
                                    value=""
                                    checked={!localFilters.genre}
                                    onChange={() => handleFilterChange('genre', '')}
                                    className="w-4 h-4 min-w-[16px] min-h-[16px] flex-shrink-0 accent-[#6A4BFB] cursor-pointer"
                                />
                                <span className="text-xs sm:text-sm text-black dark:text-white text-left font-normal">All Genres</span>
                            </label>
                            {genres.map((genre) => (
                                <label key={genre} className="flex items-center gap-3 cursor-pointer group w-full pr-2">
                                    <input
                                        type="radio"
                                        name="genre"
                                        value={genre}
                                        checked={localFilters.genre === genre}
                                        onChange={() => handleFilterChange('genre', genre)}
                                        className="w-4 h-4 min-w-[16px] min-h-[16px] flex-shrink-0 accent-[#6A4BFB] cursor-pointer"
                                    />
                                    <span className="text-xs sm:text-sm text-black dark:text-white text-left font-normal">{genre}</span>
                                </label>
                            ))}
                        </div>
                    )}
                    <div className="border-b border-gray-200 dark:border-gray-700 mt-4"></div>
                </div>

                {/* Sort By */}
                <div className="mb-4">
                    <button
                        onClick={() => toggleSection('sortBy')}
                        className="w-full flex items-center justify-between mb-3 text-sm font-medium text-black dark:text-white gap-2"
                    >
                        <span className="text-left">Sort by</span>
                        {expandedSections.sortBy ? <ChevronUp className="w-4 h-4 flex-shrink-0" /> : <ChevronDown className="w-4 h-4 flex-shrink-0" />}
                    </button>

                    {expandedSections.sortBy && (
                        <div className="space-y-2 pl-0 w-full overflow-x-auto">
                            {sortByOptions.map((option) => (
                                <label key={option.value} className="flex items-center gap-3 cursor-pointer group w-full pr-2">
                                    <input
                                        type="radio"
                                        name="sortBy"
                                        value={option.value}
                                        checked={localFilters.sort_by === option.value}
                                        onChange={() => handleFilterChange('sort_by', option.value)}
                                        className="w-4 h-4 min-w-[16px] min-h-[16px] flex-shrink-0 accent-[#6A4BFB] cursor-pointer"
                                    />
                                    <span className="text-xs sm:text-sm text-black dark:text-white text-left font-normal">{option.label}</span>
                                </label>
                            ))}
                        </div>
                    )}
                    <div className="border-b border-gray-200 dark:border-gray-700 mt-4"></div>
                </div>

                {/* Order */}
                <div className="mb-4">
                    <button
                        onClick={() => toggleSection('order')}
                        className="w-full flex items-center justify-between mb-3 text-sm font-medium text-black dark:text-white gap-2"
                    >
                        <span className="text-left">Order</span>
                        {expandedSections.order ? <ChevronUp className="w-4 h-4 flex-shrink-0" /> : <ChevronDown className="w-4 h-4 flex-shrink-0" />}
                    </button>

                    {expandedSections.order && (
                        <div className="space-y-2 pl-0 w-full overflow-x-auto">
                            {orderOptions.map((option) => (
                                <label key={option.value} className="flex items-center gap-3 cursor-pointer group w-full pr-2">
                                    <input
                                        type="radio"
                                        name="order"
                                        value={option.value}
                                        checked={localFilters.sort_order === option.value}
                                        onChange={() => handleFilterChange('sort_order', option.value)}
                                        className="w-4 h-4 min-w-[16px] min-h-[16px] flex-shrink-0 accent-[#6A4BFB] cursor-pointer"
                                    />
                                    <span className="text-xs sm:text-sm text-black dark:text-white text-left font-normal">{option.label}</span>
                                </label>
                            ))}
                        </div>
                    )}
                    <div className="border-b border-gray-200 dark:border-gray-700 mt-4"></div>
                </div>

                {/* Min Tracks */}
                <div className="mb-4">
                    <button
                        onClick={() => toggleSection('minTracks')}
                        className="w-full flex items-center justify-between mb-3 text-sm font-medium text-black dark:text-white gap-2"
                    >
                        <span className="text-left">Min. Tracks</span>
                        {expandedSections.minTracks ? <ChevronUp className="w-4 h-4 flex-shrink-0" /> : <ChevronDown className="w-4 h-4 flex-shrink-0" />}
                    </button>

                    {expandedSections.minTracks && (
                        <div className="space-y-2 pl-0 w-full overflow-x-auto">
                            {minTrackOptions.map((option) => (
                                <label key={option.value} className="flex items-center gap-3 cursor-pointer group w-full pr-2">
                                    <input
                                        type="radio"
                                        name="minTracks"
                                        value={option.value}
                                        checked={localFilters.min_tracks === option.value}
                                        onChange={() => handleFilterChange('min_tracks', option.value)}
                                        className="w-4 h-4 min-w-[16px] min-h-[16px] flex-shrink-0 accent-[#6A4BFB] cursor-pointer"
                                    />
                                    <span className="text-xs sm:text-sm text-black dark:text-white text-left font-normal">{option.label}</span>
                                </label>
                            ))}
                        </div>
                    )}
                    <div className="border-b border-gray-200 dark:border-gray-700 mt-4"></div>
                </div>

                {/* Min Popularity */}
                <div className="mb-4">
                    <button
                        onClick={() => toggleSection('minPopularity')}
                        className="w-full flex items-center justify-between mb-3 text-sm font-medium text-black dark:text-white gap-2"
                    >
                        <span className="text-left">Min Popularity</span>
                        {expandedSections.minPopularity ? <ChevronUp className="w-4 h-4 flex-shrink-0" /> : <ChevronDown className="w-4 h-4 flex-shrink-0" />}
                    </button>

                    {expandedSections.minPopularity && (
                        <div className="space-y-2 pl-0 w-full overflow-x-auto">
                            {minPopularityOptions.map((option) => (
                                <label key={option.value} className="flex items-center gap-3 cursor-pointer group w-full pr-2">
                                    <input
                                        type="radio"
                                        name="minPopularity"
                                        value={option.value}
                                        checked={localFilters.min_popularity === option.value}
                                        onChange={() => handleFilterChange('min_popularity', option.value)}
                                        className="w-4 h-4 min-w-[16px] min-h-[16px] flex-shrink-0 accent-[#6A4BFB] cursor-pointer"
                                    />
                                    <span className="text-xs sm:text-sm text-black dark:text-white text-left font-normal">{option.label}</span>
                                </label>
                            ))}
                        </div>
                    )}
                </div>

                {/* Clear Filters Button */}
                {/* {(localFilters.genre || localFilters.min_tracks || localFilters.min_popularity ||
                    localFilters.sort_by !== 'track_count' || localFilters.sort_order !== 'desc') && (
                    <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            onClick={clearFilters}
                            className="w-full px-4 py-2 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded-lg text-sm font-medium transition-colors text-black dark:text-white"
                        >
                            Clear All Filters
                        </button>
                    </div>
                )} */}
            </div>
        </div>
    );
}
