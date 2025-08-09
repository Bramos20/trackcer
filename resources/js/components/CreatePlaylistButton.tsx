import React from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function CreatePlaylistButton({ producerId }) {
  const { post, processing } = useForm();

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('producers.createPlaylist', producerId), {
      preserveScroll: true,
      onSuccess: () => {
        // optional: toast notification
      },
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <Button type="submit" variant="success" disabled={processing}>
        {processing ? 'Creating...' : 'Create Playlist'}
      </Button>
    </form>
  );
}