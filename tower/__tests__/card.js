import { render, screen } from '@testing-library/react'
import Card from '../src/app/card'
import '@testing-library/jest-dom'

describe('Card', () => {
    it('renders a title', () => {
        render(<Card title={"test"}/>)

        const title = screen.getByRole('heading', {
            name: /test/i,
        })

        expect(title).toBeInTheDocument()
    })
})
