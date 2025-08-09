import React, { type ReactNode } from 'react'
import AppSidebarLayout, { type BreadcrumbItemType } from '@/Layouts/app/app-sidebar-layout'
import { Toaster } from 'sonner'
import DataUpdateNotifier from '@/components/DataUpdateNotifier'

interface AppLayoutProps {
  children: ReactNode
  breadcrumbs?: BreadcrumbItemType[]
  user: {
    name: string
    email: string
    avatar?: string
  }
  enableDataUpdateNotifier?: boolean
}

export default function AppLayout({ children, breadcrumbs, user, enableDataUpdateNotifier = false, ...props }: AppLayoutProps) {
  return (
    <>
      <AppSidebarLayout breadcrumbs={breadcrumbs} user={user} {...props}>
        {children}
      </AppSidebarLayout>
      {enableDataUpdateNotifier && <DataUpdateNotifier />}
    </>
  )
}