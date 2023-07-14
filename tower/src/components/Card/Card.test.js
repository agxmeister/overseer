import { render, screen } from '@testing-library/react'
import '@testing-library/jest-dom'
import Card from "./Card";

describe('Card', () => {
    it('renders a title', () => {
        render(<Card title={"test"}/>)

        const title = screen.getByRole('heading', {
            name: /test/i,
        })

        expect(title).toBeInTheDocument()
    })
})
