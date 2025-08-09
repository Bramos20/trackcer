import type React from "react"
import { Head, usePage } from "@inertiajs/react"
import IntegratedLayout from '@/Layouts/integrated-layout'
import { type BreadcrumbItemType } from '@/Layouts/app/app-sidebar-layout'

interface User {
  name: string
  email: string
  profile_image?: string
}

interface AppLayoutProps {
  children: React.ReactNode
  user: User
  title?: string
  breadcrumbs?: BreadcrumbItemType[]
  enableDataUpdateNotifier?: boolean
}

export default function AppLayout({ children, user, title, breadcrumbs, enableDataUpdateNotifier = false }: AppLayoutProps) {
  const { url, props } = usePage<{
    producer?: { name: string }
    artist?: { name: string }
  }>()
  // Remove query parameters and hash from URL
  const cleanUrl = url.split('?')[0].split('#')[0]
  const segments = cleanUrl.split("/").filter(Boolean)
  
  // Generate default breadcrumbs if not provided
  let defaultBreadcrumbs: BreadcrumbItemType[]
  
  if (!breadcrumbs) {
    defaultBreadcrumbs = [
      {
        title: 'Dashboard',
        href: '/dashboard'
      }
    ]
    
    // Handle different page types
    if (segments.length > 0 && segments[0] !== 'dashboard') {
      // Check if we're on a producer show page
      if (segments[0] === 'producer' && segments.length > 1 && props.producer) {
        defaultBreadcrumbs.push({
          title: 'Producers',
          href: '/producers'
        })
        defaultBreadcrumbs.push({
          title: props.producer.name
        })
      }
      // Check if we're on an artist show page  
      else if (segments[0] === 'artists' && segments.length > 1 && props.artist) {
        defaultBreadcrumbs.push({
          title: 'Artists',
          href: '/artists'
        })
        defaultBreadcrumbs.push({
          title: props.artist.name
        })
      }
      // Default behavior for other pages
      else {
        defaultBreadcrumbs.push({
          title: segments[0].charAt(0).toUpperCase() + segments[0].slice(1)
        })
      }
    }
  } else {
    defaultBreadcrumbs = breadcrumbs
  }

  const userWithAvatar = {
    ...user,
    avatar: user.profile_image || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}`,
  }

  return (
    <>
      {title && <Head title={title} />}
      <IntegratedLayout 
        user={userWithAvatar} 
        enableDataUpdateNotifier={enableDataUpdateNotifier}
      >
        {children}
      </IntegratedLayout>
    </>
  )
}