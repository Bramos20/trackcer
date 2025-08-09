import React from "react";
import { Link } from "@inertiajs/react";
import { ChevronLeft, ChevronRight } from "lucide-react";

export default function Pagination({ meta, only = null }) {
  if (!meta) {
    console.warn("Pagination component: meta prop is undefined");
    return null;
  }

  // Determine where the links array actually is
  const links = meta.links || (meta.meta && meta.meta.links);

  if (!links || links.length === 0) {
    console.warn("Pagination component: No pagination links found in meta", meta);
    return null;
  }

  // Hide pagination if total items <= per_page
  const total = meta.total || (meta.meta && meta.meta.total) || 0;
  const per_page = meta.per_page || (meta.meta && meta.meta.per_page) || 0;

  if (total <= per_page) return null;

  return (
    <div className="flex justify-end mt-2 gap-3 items-center relative z-10">
      {links.map((link, index) => {
        // Check if this is a previous/next link
        const isPrevious = link.label.includes('Previous') || link.label.includes('&laquo;');
        const isNext = link.label.includes('Next') || link.label.includes('&raquo;');
        
        // Skip rendering if it's a disabled prev/next with no URL
        if ((isPrevious || isNext) && !link.url) {
          return null;
        }
        
        // Render chevron buttons for prev/next
        if (isPrevious || isNext) {
          return (
            <Link
              key={index}
              href={link.url || ""}
              preserveState
              preserveScroll={false}
              only={only}
              className="w-10 h-10 flex items-center justify-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
              onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
            >
              {isPrevious ? <ChevronLeft className="w-5 h-5" /> : <ChevronRight className="w-5 h-5" />}
            </Link>
          );
        }
        
        // Render page numbers
        if (!link.url) {
          // Render disabled link
          return (
            <span
              key={index}
              className={`w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-all ${
                link.active
                  ? "bg-[#6A4BFB] text-white"
                  : "text-gray-600 dark:text-gray-400 opacity-50"
              }`}
            >
              {link.label}
            </span>
          );
        }
        
        return (
          <Link
            key={index}
            href={link.url}
            preserveState
            preserveScroll={false}
            only={only}
            className={`w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-all ${
              link.active
                ? "bg-[#6A4BFB] text-white"
                : "text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
            }`}
            onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
          >
            {link.label}
          </Link>
        );
      })}
    </div>
  );
}