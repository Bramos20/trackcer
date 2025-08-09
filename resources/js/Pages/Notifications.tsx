import React, { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import AppLayout from "@/Layouts/AppLayout";
import { ChevronLeft, ChevronRight, Trash2, RotateCcw, AudioLines } from "lucide-react";
import { cn } from "@/lib/utils";
import TrashCan from "@/assets/trash-can.svg";

interface Notification {
  id: string;
  track_name: string;
  artist_name: string;
  played_by_user_id: string;
  played_by_name: string;
  track_id: string;
  producer_name: string;
  track_url: string | null;
  source: string | null;
  read_at: string | null;
  created_at: string;
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface PaginatedNotifications {
  data: Notification[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links: PaginationLink[];
  next_page_url: string | null;
  prev_page_url: string | null;
}

interface NotificationsProps {
  notifications: PaginatedNotifications;
  auth: any;
}

export default function Notifications({
   notifications: paginatedNotifications,
    auth }: NotificationsProps) {
  const [notifications, setNotifications] = useState<Notification[]>(paginatedNotifications.data);
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
  const [swipedId, setSwipedId] = useState<string | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);
  const [pagination, setPagination] = useState(paginatedNotifications);

  useEffect(() => {
    setNotifications(paginatedNotifications.data);
    setPagination(paginatedNotifications);
    setSelectedIds(new Set());
  }, [paginatedNotifications]);

  const handleSelectAll = () => {
    if (selectedIds.size === notifications.length) {
      setSelectedIds(new Set());
    } else {
      setSelectedIds(new Set(notifications.map((n: Notification) => n.id)));
    }
  };

  const handleSelect = (id: string) => {
    const newSelected = new Set(selectedIds);
    newSelected.has(id) ? newSelected.delete(id) : newSelected.add(id);
    setSelectedIds(newSelected);
  };

  const handleDelete = async (id: string) => {
    try {
      const response = await fetch(`/notifications/${id}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });
      if (response.ok) {
        setNotifications((prev) => prev.filter((n) => n.id !== id));
        setSwipedId(null);
      }
    } catch (error) {
      console.error('Error deleting notification:', error);
    }
  };

  const handleBulkDelete = async () => {
    if (selectedIds.size === 0) return;
    setIsDeleting(true);
    try {
      const response = await fetch('/notifications/bulk-delete', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ ids: Array.from(selectedIds) }),
      });
      if (response.ok) {
        setNotifications((prev) => prev.filter((n) => !selectedIds.has(n.id)));
        setSelectedIds(new Set());
      }
    } catch (error) {
      console.error(error);
    } finally {
      setIsDeleting(false);
    }
  };

  const handleDeleteAll = async () => {
    if (!confirm(`Delete all ${pagination.total}?`)) return;
    setIsDeleting(true);
    try {
      const response = await fetch('/notifications/delete-all', {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });
      if (response.ok) window.location.href = '/notifications';
    } catch (error) {
      console.error(error);
    } finally {
      setIsDeleting(false);
    }
  };

  /** ✅ Toggle open/close on click */
  const toggleSlide = (id: string) => {
    setSwipedId((prev) => (prev === id ? null : id));
  };

  return (
    <AppLayout user={auth?.user}>
      <div className="min-h-screen bg-background space-y-6">
        <div className="p-4 space-y-4">
      <h2 className="text-3xl lg:text-4xl font-normal">Notifications</h2>
        {/* Header */}
        <div className="px-4 py-3 flex items-center justify-between flex-wrap gap-5">
          <div className="flex items-center gap-3 flex-wrap">
            <Checkbox
              checked={selectedIds.size === notifications.length && notifications.length > 0}
              onCheckedChange={handleSelectAll}
              className="data-[state=checked]:!bg-[#EA6115] data-[state=checked]:!border-[#EA6115] border-[#19191950] dark:border-white/50"
            />
            <Button variant="ghost" size="sm" className="p-1 h-auto">
              <RotateCcw className="h-4 w-4 text-[#19191950] dark:text-white/50" />
            </Button>

            {selectedIds.size > 0 && (
              <button
                className="text-white whitespace-nowrap flex items-center gap-2 text-sm bg-[rgb(255,129,59)] rounded-sm px-2 py-1"
                onClick={handleBulkDelete}
                disabled={isDeleting}
              >
                <Trash2 size={20} />
                <span>Delete ({selectedIds.size})</span>
              </button>
            )}

            {notifications.length > 0 && (
              <button
                className="text-white whitespace-nowrap flex items-center gap-2 text-sm bg-[rgb(255,129,59)] rounded-sm px-2 py-1"
                onClick={handleDeleteAll}
                disabled={isDeleting}
              >
                <Trash2 size={20} />
                <span>Delete All ({pagination.total})</span>
              </button>
            )}
          </div>
          <div className="text-sm text-[#19191950] dark:text-white/50 font-medium whitespace-nowrap">
            {(pagination.current_page - 1) * pagination.per_page + 1}–
            {Math.min(pagination.current_page * pagination.per_page, pagination.total)} of{" "}
            {pagination.total.toLocaleString()}
          </div>
        </div>

        {/* Notifications */}
        <div className="space-y-3">
          {notifications.length === 0 && (
            <div className="text-center py-10 text-muted-foreground">
              <p>No notifications available.</p>
            </div>
          )}
          {notifications.map((n) => (
            <NotificationItem
              key={n.id}
              notification={n}
              isSelected={selectedIds.has(n.id)}
              onSelect={() => handleSelect(n.id)}
              isSwipedOpen={swipedId === n.id}
              onDelete={() => handleDelete(n.id)}
              onToggle={() => toggleSlide(n.id)}
            />
          ))}
        </div>

        {/* Pagination Controls */}
            {pagination.last_page > 1 && (
              <div className="flex items-center justify-end gap-1 mt-6">
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => window.location.href = pagination.prev_page_url || '#'}
                  disabled={!pagination.prev_page_url}
                  className="h-9 w-9"
                >
                  <ChevronLeft className="h-4 w-4" />
                </Button>

                <div className="flex items-center gap-1">
                  {pagination.links.slice(1, -1).map((link: PaginationLink, index: number) => {
                    const links = pagination.links.slice(1, -1);
                    const currentIndex = links.findIndex((l: PaginationLink) => l.active);
                    const distance = Math.abs(index - currentIndex);

                    // Hide distant pages on mobile
                    const hideOnMobile = distance > 1;

                    return (
                      <Button
                        key={link.label}
                        variant={link.active ? "default" : "ghost"}
                        size="sm"
                        onClick={() => window.location.href = link.url || '#'}
                        className={`h-9 px-4 text-sm sm:text-base ${hideOnMobile ? 'hidden sm:flex' : ''} ${
                          link.active
                            ? 'bg-[#6A4BFB] hover:bg-[#6A4BFB]/90 text-white dark:bg-[#6A4BFB] dark:text-white dark:hover:bg-[#6A4BFB]/90'
                            : ''
                        }`}
                      >
                        {link.label}
                      </Button>
                    );
                  })}
                </div>

                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => window.location.href = pagination.next_page_url || '#'}
                  disabled={!pagination.next_page_url}
                  className="h-9 w-9"
                >
                  <ChevronRight className="h-4 w-4" />
                </Button>
              </div>
            )}
      </div>
      </div>
    </AppLayout>
  );
}

interface NotificationItemProps {
  notification: Notification;
  isSelected: boolean;
  onSelect: () => void;
  isSwipedOpen: boolean;
  onDelete: () => void;
  onToggle: () => void;
}

function NotificationItem({
  notification,
  isSelected,
  onSelect,
  isSwipedOpen,
  onDelete,
  onToggle
}: NotificationItemProps) {
  return (
    <div className="relative rounded-lg overflow-hidden min-h-[104px]">
      {/* Delete background */}
      <div
        className={cn(
          "absolute right-0 top-0 h-full flex items-center justify-center transition-all duration-300 ease-in-out rounded-lg pl-3",
          isSwipedOpen ? "translate-x-0" : "translate-x-full"
        )}
        style={{ width: 100, backgroundColor: "rgb(255,129,59)" }}
      >
        <Button
          variant="ghost"
          size="sm"
          onClick={onDelete}
          className="text-white hover:text-white hover:bg-orange-500/20 p-2"
        >
          <img src={TrashCan} alt="Delete" className="h-5 w-5" />
        </Button>
      </div>

      {/* Main content */}
      <div
        onClick={onToggle}
        className={cn(
          "relative transition-all duration-300 ease-in-out cursor-pointer select-none min-h-[104px] rounded-lg",
          isSwipedOpen
            ? "bg-[#ffebe0] dark:bg-[#372a6f]"
            : "bg-[rgba(255,255,255,0.51)] dark:bg-[rgba(123,123,123,0.11)]"
        )}
        style={{ transform: `translateX(${isSwipedOpen ? "-80px" : "0"})` }}
      >
        <div className="flex items-start gap-3 p-4 pr-20 sm:pr-24">
          {/* Checkbox */}
          <div className="flex-shrink-0 mr-1" onClick={(e) => e.stopPropagation()}>
            <Checkbox
              checked={isSelected}
              onCheckedChange={onSelect}
              className="data-[state=checked]:!bg-[#EA6115] data-[state=checked]:!border-[#EA6115] border-[#19191950] dark:border-white/50"
            />
          </div>

          <AudioLines className="w-4 h-4 mt-0.5 flex-shrink-0" />

          {/* Audio icon */}
          {/* <div className="flex-shrink-0 pt-1">
            <span className="h-4 w-4 text-muted-foreground">
              <svg
                width="21"
                height="14"
                viewBox="0 0 21 14"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <rect width="1" height="14" fill="currentColor"/>
                <rect x="2" y="4" width="1" height="10" fill="currentColor"/>
                <rect x="4" y="2" width="1" height="12" fill="currentColor"/>
                <rect x="6" y="6" width="1" height="8" fill="currentColor"/>
                <rect x="8" y="1" width="1" height="13" fill="currentColor"/>
                <rect x="10" y="3" width="1" height="11" fill="currentColor"/>
                <rect x="12" y="5" width="1" height="9" fill="currentColor"/>
                <rect x="14" y="2" width="1" height="12" fill="currentColor"/>
                <rect x="16" y="4" width="1" height="10" fill="currentColor"/>
                <rect x="18" y="7" width="1" height="7" fill="currentColor"/>
                <rect x="20" y="9" width="1" height="5" fill="currentColor"/>
              </svg>
            </span>
          </div> */}

          {/* Content */}
          <div className="flex-1 min-w-0">
            <p className="text-sm text-muted-foreground leading-relaxed">
              A track associated with <strong>{notification.producer_name}</strong> was just played.{" "}
              <strong>{notification.track_name}</strong> by {notification.artist_name}
            </p>

            {/* Play Button Section */}
            {notification.track_url && notification.source && (
              <div className="mt-2">
                {notification.source === "spotify" ? (
                  <a
                    href={notification.track_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-block"
                    onClick={(e) => e.stopPropagation()}
                  >
                    <button className="flex items-center gap-2 bg-[#1DB954] text-white z-[100] rounded-sm px-2 py-1.5">
                      <img
                        src="https://upload.wikimedia.org/wikipedia/commons/8/84/Spotify_icon.svg"
                        alt="Spotify"
                        className="w-4 h-4 flex-shrink-0"
                      />
                      <span className="truncate flex-1 text-center font-inter text-xs">
                        Play on Spotify
                      </span>
                    </button>
                  </a>
                ) : (
                  <a
                    href={notification.track_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-block"
                    onClick={(e) => e.stopPropagation()}
                  >
                    <button className="flex items-center gap-2 bg-gradient-to-r from-[#FA233B] to-[#FB5C74] text-white z-[100] rounded-sm px-2 py-1.5">
                      <img
                        src="/images/Apple_Music_Icon_wht_lg_072420.svg"
                        alt="Apple Music"
                        className="w-4 h-4 flex-shrink-0"
                      />
                      <span className="truncate flex-1 text-center font-inter text-xs">
                        Play on Apple Music
                      </span>
                    </button>
                  </a>
                )}
              </div>
            )}
          </div>
        </div>

        {/* Badge + Time */}
        <div className="flex flex-col gap-2 absolute right-3 items-end top-0 justify-between h-full py-3">
          <span
            className={cn(
              "text-xs px-4 py-1 rounded-xl",
              !notification.read_at && "bg-[#EA6115] text-white"
            )}
          >
            {!notification.read_at && "New"}
          </span>
          <span className="text-xs text-muted-foreground whitespace-nowrap">
            {notification.created_at}
          </span>
        </div>
      </div>
    </div>
  );
}

function getTimeDifferenceText(pastTimeStr) {
  const past = new Date(pastTimeStr.replace(" ", "T")); // Make it ISO format
  const now = new Date();
  const diffMs = now.getTime() - past.getTime();

  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
  const diffDays = Math.floor(diffHours / 24);

  if (diffHours < 24) {
    return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
  } else {
    return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
  }
}