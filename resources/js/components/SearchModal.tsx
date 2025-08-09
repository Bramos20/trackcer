import React, { useState, useEffect, useRef } from 'react'
import { X, Search, Music, Users, Mic2 } from 'lucide-react'
import { Link } from '@inertiajs/react'
import { cn } from '@/lib/utils'

interface SearchResult {
  id: string
  name: string
  type: 'artist' | 'producer' | 'track'
  image?: string
  albumArtwork?: string
  artistName?: string
  page?: number
}

interface SearchModalProps {
  isOpen: boolean
  onClose: () => void
}

export default function SearchModal({ isOpen, onClose }: SearchModalProps) {
  const [searchQuery, setSearchQuery] = useState('')
  const [searchResults, setSearchResults] = useState<SearchResult[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [selectedIndex, setSelectedIndex] = useState(-1)
  const searchInputRef = useRef<HTMLInputElement>(null)
  const modalRef = useRef<HTMLDivElement>(null)
  const resultsRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (isOpen && searchInputRef.current) {
      searchInputRef.current.focus()
    }
  }, [isOpen])

  // Scroll selected item into view
  useEffect(() => {
    if (selectedIndex >= 0 && resultsRef.current) {
      const selectedElement = resultsRef.current.querySelector(
        `.p-2 > :nth-child(${selectedIndex + 1})`
      )
      if (selectedElement) {
        selectedElement.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
      }
    }
  }, [selectedIndex])

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose()
        return
      }

      if (searchResults.length === 0) return

      switch (e.key) {
        case 'ArrowDown':
          e.preventDefault()
          setSelectedIndex(prev =>
            prev < searchResults.length - 1 ? prev + 1 : 0
          )
          break
        case 'ArrowUp':
          e.preventDefault()
          setSelectedIndex(prev =>
            prev > 0 ? prev - 1 : searchResults.length - 1
          )
          break
        case 'Enter':
          e.preventDefault()
          if (selectedIndex >= 0 && selectedIndex < searchResults.length) {
            const selected = searchResults[selectedIndex]
            window.location.href = getLink(selected)
            onClose()
          }
          break
      }
    }

    if (isOpen) {
      document.addEventListener('keydown', handleKeyDown)
    }

    return () => {
      document.removeEventListener('keydown', handleKeyDown)
    }
  }, [isOpen, onClose, searchResults, selectedIndex])

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (modalRef.current && !modalRef.current.contains(e.target as Node)) {
        onClose()
      }
    }

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside)
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside)
    }
  }, [isOpen, onClose])

  useEffect(() => {
    const delayDebounceFn = setTimeout(() => {
      if (searchQuery.trim()) {
        performSearch()
      } else {
        setSearchResults([])
        setSelectedIndex(-1)
      }
    }, 300)

    return () => clearTimeout(delayDebounceFn)
  }, [searchQuery])

  const performSearch = async () => {
    setIsLoading(true)
    try {
      const response = await window.axios.get('/search', {
        params: { q: searchQuery }
      })
      setSearchResults(response.data.results || [])
      setSelectedIndex(-1) // Reset selection when new results come in
    } catch (error) {
      console.error('Search error:', error)
      setSearchResults([])
      setSelectedIndex(-1)
    } finally {
      setIsLoading(false)
    }
  }

  const getIcon = (type: string) => {
    switch (type) {
      case 'artist':
        return <Mic2 className="h-4 w-4" />
      case 'producer':
        return <Users className="h-4 w-4" />
      case 'track':
        return <Music className="h-4 w-4" />
      default:
        return null
    }
  }

  const getLink = (result: SearchResult) => {
    switch (result.type) {
      case 'artist':
        // Artists use the artist name in the URL
        return `/artists/${result.id}`
      case 'producer':
        // Producers use /producer/{id} (singular)
        return `/producer/${result.id}`
      case 'track':
        // For tracks, search by the track name instead of using track ID
        // This matches how the Tracks page handles search
        const searchParam = encodeURIComponent(result.name)
        const pageParam = result.page ? `&page=${result.page}` : ''
        return `/tracks?search=${searchParam}${pageParam}`
      default:
        return '#'
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-[100] flex items-start justify-center pt-20 bg-black/50 backdrop-blur-sm">
      <div
        ref={modalRef}
        className="bg-[#F2F2F2] dark:bg-[#000000]/69 rounded-2xl shadow-2xl w-full max-w-2xl mx-4"
      >
        <div className="flex items-center gap-4 p-4 border-b border-gray-200 dark:border-gray-800">
          <Search className="h-5 w-5 text-gray-400" />
          <input
            ref={searchInputRef}
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Search for artists, producers, or tracks..."
            className="flex-1 bg-transparent outline-none text-gray-900 dark:text-white placeholder-gray-500"
          />
          <button
            onClick={onClose}
            className="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
          >
            <X className="h-5 w-5 text-gray-500" />
          </button>
        </div>

        <div className="max-h-[60vh] overflow-y-auto" ref={resultsRef}>
          {isLoading ? (
            <div className="p-8 text-center text-gray-500">
              <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
            </div>
          ) : searchResults.length > 0 ? (
            <div className="p-2">
              {searchResults.map((result, index) => (
                <Link
                  key={`${result.type}-${result.id}`}
                  href={getLink(result)}
                  onClick={onClose}
                  onMouseEnter={() => setSelectedIndex(index)}
                  className={cn(
                    "flex items-center gap-4 p-3 rounded-lg transition-all duration-200",
                    index === selectedIndex
                      ? "bg-[#6A4BFB]/10 text-[#6A4BFB] dark:bg-[#6A4BFB]/20 dark:text-white"
                      : "hover:bg-[#6A4BFB]/10 hover:text-[#6A4BFB] dark:hover:bg-[#6A4BFB]/20 dark:hover:text-white"
                  )}
                >
                  <div className="relative flex-shrink-0">
                    {result.type === 'track' && result.albumArtwork ? (
                      <img
                        src={result.albumArtwork}
                        alt={result.name}
                        className="w-12 h-12 rounded-lg object-cover"
                      />
                    ) : result.image ? (
                      <img
                        src={result.image}
                        alt={result.name}
                        className="w-12 h-12 rounded-full object-cover"
                      />
                    ) : (
                      <div className="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                        {getIcon(result.type)}
                      </div>
                    )}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="font-medium text-gray-900 dark:text-white truncate">
                      {result.name}
                    </div>
                    <div className="text-sm text-gray-500 flex items-center gap-2">
                      {getIcon(result.type)}
                      <span className="capitalize">{result.type}</span>
                      {result.type === 'track' && result.artistName && (
                        <>
                          <span className="text-gray-400">•</span>
                          <span>{result.artistName}</span>
                        </>
                      )}
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          ) : searchQuery.trim() ? (
            <div className="p-8 text-center text-gray-500">
              No results found for "{searchQuery}"
            </div>
          ) : (
            <div className="p-8 text-center text-gray-500">
              Start typing to search...
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
