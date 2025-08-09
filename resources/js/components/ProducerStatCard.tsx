import React from "react"
import { useTheme } from "next-themes"

export default function ProducerStatCard({ title, value, subtitle, icon }) {
  const { theme } = useTheme()

  return (
    <div className="relative w-full group overflow-hidden transition-all duration-300 h-[140px] sm:h-[180px]"
      style={{ isolation: 'isolate' }}>
      {/* Background shape without blur effect */}
      <div className="absolute inset-0">
        {/* Mobile SVG */}
        <svg
          width="100%"
          height="100%"
          viewBox="0 0 368 120"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          preserveAspectRatio="none"
          className="absolute inset-0 w-full h-full sm:hidden"
        >
          <path
            d="M0 93.289V27.3728C0 12.7142 11.8831 0.831055 26.5417 0.831055H285.204C292.912 0.831055 299.162 7.08034 299.162 14.7892C299.162 22.4981 305.411 28.7474 313.12 28.7474H341.458C356.117 28.7474 368 40.6305 368 55.2891V93.289C368 107.948 356.117 119.831 341.458 119.831H26.5418C11.8832 119.831 0 107.948 0 93.289Z"
            fill={theme === 'dark' ? 'rgba(25, 25, 25, 0.51)' : '#F2F2F2'}
          />
        </svg>

        {/* Desktop SVG */}
        <svg
          width="100%"
          height="100%"
          viewBox="0 0 280 160"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          preserveAspectRatio="xMinYMid meet"
          className="absolute inset-0 w-full h-full hidden sm:block"
        >
          <path
            d="M0 120V40C0 17.9086 17.9086 0 40 0H192.607C204.198 0 213.594 9.39577 213.594 20.9861C213.594 32.5763 222.989 41.9721 234.58 41.9721H246.797C265.134 41.9721 280 56.8377 280 75.1754V120C280 142.091 262.091 160 240 160H40C17.9086 160 0 142.091 0 120Z"
            fill={theme === 'dark' ? 'rgba(25, 25, 25, 0.51)' : '#F2F2F2'}
          />
        </svg>
      </div>

      {/* Content */}
      <div className="relative z-10 p-4 sm:p-5 flex flex-col h-full overflow-hidden">
        {/* Top section with icon and title */}
        <div className="flex items-start gap-3 ml-2">
          <div className="flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 bg-[#E4E4E4] dark:bg-[#FFFFFF14] rounded-full flex-shrink-0">
            {React.cloneElement(icon, { className: "w-4 h-4 sm:w-5 sm:h-5 text-gray-600 dark:text-gray-400" })}
          </div>
          <h3 className="text-xs sm:text-base font-medium truncate pt-1.5 sm:pt-2">{title}</h3>
        </div>

        {/* Main content */}
        <div className="flex-1 flex items-center w-full">
          <div className="flex items-center gap-2 sm:gap-3 pl-[64px] sm:pl-[72px]">
            <div className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
              {value}
            </div>
          </div>
        </div>

        {/* Subtitle/Footer */}
        {subtitle && (
          <div className="flex items-center gap-3 ml-2">
            <div className="flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10">
              <div className="w-1 h-2.5 sm:h-3 bg-orange-500 rounded-full"></div>
            </div>
            <p className="text-gray-600 dark:text-gray-400 text-xs sm:text-sm line-clamp-2">{subtitle}</p>
          </div>
        )}
      </div>
    </div>
  )
}